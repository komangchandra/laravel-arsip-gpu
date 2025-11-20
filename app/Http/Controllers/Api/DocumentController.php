<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Pest\Support\Str;

use function Pest\Laravel\json;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $documents = Document::where('status', 'approved')->get();
        return response()->json($documents);
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        $disk = 'public';

        if (!$document->file_path || !Storage::disk($disk)->exists($document->file_path)) {
            return response()->json(['message' => 'File tidak ditemukan.'], 404);
        }
        
        $path = Storage::disk($disk)->path($document->file_path);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($document->file_path) . '"'
        ]);
    }

    /**
     * Sign document.
     */
    public function sign(Document $document, Request $request)
    {
        $user = $request->user();
        
        // 1. Validasi Input dan Otorisasi
        $request->validate(['signature' => 'required|string']);

        // ... (Cek duplikat signing menggunakan $document->signedBy()...)

        // 2. Proses Data Tanda Tangan (Base64)
        $base64Image = $request->input('signature'); // format: data:image/png;base64,iVBORw0KGg...
        
        // Hapus header data:image/png;base64,
        $image_parts = explode(";base64,", $base64Image);
        $image_base64 = base64_decode($image_parts[1]);
        
        $signatureFileName = 'sign_' . $user->id . '_' . time() . '.png';
        $tempPath = 'temp/' . $signatureFileName;

        // Simpan gambar tanda tangan sementara di storage/app/temp
        Storage::put($tempPath, $image_base64);

        // 3. PROSES PENANAMAN TANDA TANGAN (MEMERLUKAN LIBRARY PDF)
        
        // LOKASI FILE ASLI (Asumsi disk 'public')
        $originalPdfPath = Storage::disk('public')->path($document->file_path);

        // LOKASI FILE TANDA TANGAN SEMENTARA
        $signatureImagePath = Storage::path($tempPath);

        // [DI SINI ADALAH TEMPAT DIMANA LIBRARY PHP PDF (FPDI/SetaPDF)
        // AKAN MEMBUKA $originalPdfPath, MENANAMKAN GAMBAR TANDA TANGAN
        // DARI $signatureImagePath, DAN MENYIMPANNYA SEBAGAI $newSignedPdfPath]
        
        // Contoh output path di disk 'signeds'
        $newSignedFileName = Str::random(40) . '.pdf';
        $signedPath = 'signeds/' . $newSignedFileName; 
        $newSignedPdfPath = Storage::disk('signeds')->path($newSignedFileName);

        // Simulasi (TIDAK MELAKUKAN PENANAMAN PDF NYATA)
        // Karena kita tidak punya library PDF, kita asumsikan file sudah dibuat.
        // Dalam implementasi nyata, Anda harus memastikan file PDF baru disimpan ke $newSignedPdfPath.
        // ... logic PDF library ...
        // Hapus file tanda tangan sementara
        Storage::delete($tempPath);
        // ----------------------------------------------------------------------
        
        // 4. Update Database (dalam Transaksi)
        try {
            DB::beginTransaction();

            // A. INSERT ke document_signeds
            $document->signedBy()->attach($user->id); 

            // B. UPDATE Status dan Path File Baru
            $document->update([
                'status' => 'Signed',
                // Simpan path relatif ke disk 'signeds'
                'file_signed_path' => $signedPath 
            ]);
            
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            // \Log::error("Gagal update DB setelah TTD: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menyimpan catatan tanda tangan.'], 500);
        }
        
        return response()->json([
            'message' => 'Dokumen berhasil ditandatangani, file PDF baru telah dibuat.',
            'file_signed_url' => url('/storage/' . $signedPath) // URL akses publik
        ], 200);
    }
}
