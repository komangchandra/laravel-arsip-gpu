<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Category;
use App\Models\Document;
use App\Pdf\RotatableFpdi;
use App\Services\DocumentSigningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use Symfony\Component\Process\Process;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Document::class);
        $query = Document::with([
            'creator',
            'category',
            'checkedBy',
            'signedBy',
            'signRoutes.signer',
        ])
            ->accessibleTo($request->user())
            ->whereIn('status', ['routing', 'waiting_for_signatures', 'ready_to_sign', 'signed'])

            // ⛔ exclude document yang sudah disign Ferry
            ->whereDoesntHave('signedBy', function ($q) {
                $q->where('email', 'ferry.juanda@gorbyputrautama.com');
            });

        /*
        |--------------------------------------------------------------------------
        | FILTER TANGGAL
        |--------------------------------------------------------------------------
        */
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $documents = $query->latest()->get();

        return view('dashboard.documents.index', compact('documents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Document::class);
        $categories = Category::all();

        return view('dashboard.documents.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Document::class);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'file_path' => 'required|file|mimes:pdf,doc,docx',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $uploadedFile = $request->file('file_path');
        $path = $uploadedFile->store('documents', 'documents');

        if (strtolower($uploadedFile->getClientOriginalExtension()) === 'pdf') {
            try {
                $this->ensurePdfIsFpdiCompatible(Storage::disk('documents')->path($path));
            } catch (RuntimeException $e) {
                Storage::disk('documents')->delete($path);

                return back()->withInput()->withErrors(['file_path' => $e->getMessage()]);
            }
        }

        $document = new Document;
        $document->title = $validated['title'];
        $document->file_path = $path;
        $document->status = 'uploaded';
        $document->created_by = Auth::id();
        $document->category_id = $validated['category_id'] ?? null;
        $document->save();

        return redirect()->route('dashboard.recently-uploaded.index')->with('success', 'Document uploaded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document)
    {
        $this->authorize('update', $document);
        $document->load('signRoutes.signer');
        $categories = Category::all();

        return view('dashboard.documents.edit', compact(['document', 'categories']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document, DocumentSigningService $signingService)
    {
        $this->authorize('update', $document);
        $workflowLocked = $document->routing_started_at !== null;
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'file_path' => [$workflowLocked ? 'prohibited' : 'nullable', 'file', 'mimes:pdf,doc,docx'],
            'status' => [$workflowLocked ? 'prohibited' : 'required', 'string'],
            'action' => ['nullable', 'in:save,request_revision'],
        ]);

        $updates = [
            'title' => $validated['title'],
            'category_id' => $validated['category_id'] ?? $document->category_id,
        ];

        if (! $workflowLocked && $request->hasFile('file_path')) {
            if ($document->file_path && Storage::disk('documents')->exists($document->file_path)) {
                Storage::disk('documents')->delete($document->file_path);
            }

            $updates['file_path'] = $request->file('file_path')->store('documents', 'documents');
        }

        if (! $workflowLocked) {
            $updates['status'] = $validated['status'];
        }

        $document->update($updates);

        if ($request->input('action') === 'request_revision') {
            $signingService->markNeedsRevision($document);
        }

        // Tambahkan user ke pivot checked_by
        $document->checkedBy()->syncWithoutDetaching([Auth::id()]);

        return redirect()
            ->route('dashboard.documents.index')
            ->with(
                'success',
                $request->input('action') === 'request_revision'
                    ? 'Dokumen ditandai perlu revisi dan routing dihentikan.'
                    : 'Document updated successfully.'
            );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);
        if ($document->file_path && Storage::disk('documents')->exists($document->file_path)) {
            Storage::disk('documents')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('dashboard.documents.index')->with('success', 'Document deleted successfully.');
    }

    public function sign(Document $document)
    {
        return view('dashboard.documents.sign', compact('document'));
    }

    public function signStore(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        // Signed pages (array page => base64PNG)
        $signedPages = json_decode($request->signed_pages, true);

        // Original PDF
        $originalPdfPath = Storage::disk('documents')->path($document->file_path);

        // Output PDF
        $newFilePath = 'documents/signed_'.time().'.pdf';
        $outputPdfPath = Storage::disk('documents')->path($newFilePath);

        // Start FPDI
        $this->ensurePdfIsFpdiCompatible($originalPdfPath);
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($originalPdfPath);
        // try {
        //     $pdf = new Fpdi();
        //     $pageCount = $pdf->setSourceFile($originalPdfPath);

        // } catch (CrossReferenceException $e) {
        //     return back()->with('error', 'PDF_NOT_COMPATIBLE');
        // }

        for ($page = 1; $page <= $pageCount; $page++) {

            $tpl = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($tpl);

            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

            if (isset($signedPages[$page])) {

                // Decode base64 PNG
                $imgData = str_replace('data:image/png;base64,', '', $signedPages[$page]);
                $imgData = base64_decode($imgData);

                // Temp file
                $tmpImg = storage_path("app/temp_sign_{$page}.png");
                file_put_contents($tmpImg, $imgData);

                // Render ke PDF
                $pdf->Image(
                    $tmpImg,
                    0,
                    0,
                    $size['width'],
                    $size['height']
                );

                unlink($tmpImg);
            }
        }

        // Simpan PDF final
        $pdf->Output($outputPdfPath, 'F');

        // ================================
        // HAPUS FILE LAMA
        // ================================
        if ($document->file_path && Storage::disk('documents')->exists($document->file_path)) {
            Storage::disk('documents')->delete($document->file_path);
        }

        // Tentukan status berdasarkan tombol yang diklik
        $status = $request->action_type === 'needs_revision'
                    ? 'needs_revision'
                    : 'signed';

        // ================================
        // UPDATE PATH DI TABLE DOCUMENTS
        // ================================
        $document->update([
            'file_path' => $newFilePath,
            'status' => $status,
        ]);

        // ================================
        // PIVOT — insert siapa yang tanda tangan
        // ================================
        $document->signedBy()->attach(Auth::id());

        return redirect()
            ->route('dashboard.documents.index')
            ->with('success', 'Document tertandatangani.');
    }

    public function stamp(Document $document)
    {
        $this->authorize('stamp', $document);

        return view('dashboard.documents.stamp', compact('document'));
    }

    public function stampStore(Request $request, Document $document)
    {
        $this->authorize('stamp', $document);

        $stampsData = json_decode($request->stamps, true);
        if (! $stampsData) {
            return back()->with('error', 'Tidak ada stampel untuk disimpan.');
        }

        $original = Storage::disk('documents')->path($document->file_path);

        $this->ensurePdfIsFpdiCompatible($original);
        $pdf = new RotatableFpdi;
        $pageCount = $pdf->setSourceFile($original);

        for ($page = 1; $page <= $pageCount; $page++) {
            $tpl = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($tpl);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);

            // Jika ada stempel di halaman ini
            if (isset($stampsData[$page])) {
                foreach ($stampsData[$page] as $s) {
                    $type = $s['type']; // "gpu" atau "ge"
                    $stampPath = Storage::disk('signature-assets')->path("stampel-$type.png");
                    if (! file_exists($stampPath)) {
                        continue;
                    }

                    // Konversi koordinat relatif ke PDF asli
                    $x = $s['x_ratio'] * $size['width'];
                    $y = $s['y_ratio'] * $size['height'];
                    $w = $s['width_ratio'] * $size['width'];
                    $h = $s['height_ratio'] * $size['height'];

                    $rotation = max(-360, min(360, (float) ($s['rotation'] ?? 0)));
                    $pdf->rotatedImage($stampPath, $x, $y, $w, $h, $rotation);
                }
            }
        }

        // timpa file lama
        $pdf->Output('F', Storage::disk('documents')->path($document->file_path));

        $document->update([
            'status' => 'stamped',
        ]);

        return redirect()->route('dashboard.full-sign.index')
            ->with('success', 'Stampel diterapkan.');
    }

    public function archive(Document $document)
    {
        $this->authorize('archive', $document);

        $document->update([
            'status' => DocumentStatus::Archived,
        ]);

        return redirect()
            ->route('dashboard.archiveds.index')
            ->with('success', 'Dokumen berhasil diarsipkan.');
    }

    public function revisiStore(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        // Signed pages (array page => base64PNG)
        $signedPages = json_decode($request->signed_pages, true);

        // Original PDF
        $originalPdfPath = Storage::disk('documents')->path($document->file_path);

        // Output PDF
        $newFilePath = 'documents/needRevisi_'.time().'.pdf';
        $outputPdfPath = Storage::disk('documents')->path($newFilePath);

        // Start FPDI
        $this->ensurePdfIsFpdiCompatible($originalPdfPath);
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($originalPdfPath);

        for ($page = 1; $page <= $pageCount; $page++) {

            $tpl = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($tpl);

            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

            if (isset($signedPages[$page])) {

                // Decode base64 PNG
                $imgData = str_replace('data:image/png;base64,', '', $signedPages[$page]);
                $imgData = base64_decode($imgData);

                // Temp file
                $tmpImg = storage_path("app/temp_sign_{$page}.png");
                file_put_contents($tmpImg, $imgData);

                // Render ke PDF
                $pdf->Image(
                    $tmpImg,
                    0,
                    0,
                    $size['width'],
                    $size['height']
                );

                unlink($tmpImg);
            }
        }

        // Simpan PDF final
        $pdf->Output($outputPdfPath, 'F');

        // ================================
        // HAPUS FILE LAMA
        // ================================
        if ($document->file_path && Storage::disk('documents')->exists($document->file_path)) {
            Storage::disk('documents')->delete($document->file_path);
        }

        // ================================
        // UPDATE PATH DI TABLE DOCUMENTS
        // ================================
        $document->update([
            'file_path' => $newFilePath,
            'status' => 'needs_revision',
        ]);

        // ================================
        // PIVOT — insert siapa yang tanda tangan
        // ================================
        $document->signedBy()->attach(Auth::id());

        return redirect()
            ->route('dashboard.documents.index')
            ->with('success', 'Document perlu direvisi.');
    }

    public function annotate(Document $document)
    {
        $this->authorize('annotate', $document);

        return view('dashboard.documents.annotate', compact('document'));
    }

    public function annotateUpload(Request $request, Document $document)
    {
        $this->authorize('annotate', $document);

        $request->validate([
            'annotated_pdf' => 'required|mimes:pdf',
        ]);

        // Hapus file lama
        if (Storage::disk('documents')->exists($document->file_path)) {
            Storage::disk('documents')->delete($document->file_path);
        }

        // Upload file baru
        $newPath = $request->file('annotated_pdf')->store('documents', 'documents');

        // Update DB
        $document->update([
            'file_path' => $newPath,
            'status' => 'updated',
        ]);

        return redirect()
            ->route('dashboard.documents.index')
            ->with('success', 'PDF berhasil ditimpa dengan hasil coretan.');
    }

    public function download(Document $document)
    {
        $this->authorize('download', $document);
        $filePath = Storage::disk('documents')->path($document->file_path);

        if (! file_exists($filePath)) {
            return redirect()->back()->with('error', 'File tidak ditemukan.');
        }

        return response()->download($filePath, $document->title.'.'.pathinfo($filePath, PATHINFO_EXTENSION));
    }

    public function signTempel(Document $document)
    {
        return view('dashboard.documents.sign-tempel', compact('document'));
    }

    public function signTempelStore(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        $stampsData = json_decode($request->stamps, true);
        if (! $stampsData) {
            return back()->with('error', 'Tidak ada sign untuk disimpan.');
        }

        $original = Storage::disk('documents')->path($document->file_path);

        $this->ensurePdfIsFpdiCompatible($original);
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($original);

        // map tipe ke nama file aktual
        $stampMap = [
            'gpu' => 'sign-wahyu.png',
            'ge' => 'sign-arif.png',
        ];

        for ($page = 1; $page <= $pageCount; $page++) {
            $tpl = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($tpl);

            // lebih aman tentukan orientasi dari ukuran
            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);

            // Jika ada stempel di halaman ini
            if (isset($stampsData[$page])) {
                foreach ($stampsData[$page] as $s) {
                    $type = $s['type']; // "gpu" atau "ge"
                    if (! isset($stampMap[$type])) {
                        continue;
                    }
                    $stampPath = Storage::disk('signature-assets')->path($stampMap[$type]);
                    if (! file_exists($stampPath)) {
                        continue;
                    }

                    // Konversi koordinat relatif ke PDF asli
                    $x = $s['x_ratio'] * $size['width'];
                    $y = $s['y_ratio'] * $size['height'];
                    $w = $s['width_ratio'] * $size['width'];
                    $h = $s['height_ratio'] * $size['height'];

                    // Perhatikan: parameter rotasi tergantung dukungan library Image()
                    $rotation = isset($s['rotation']) ? $s['rotation'] : 0;
                    $pdf->Image($stampPath, $x, $y, $w, $h, '', '', '', false, 300, '', false, false, 0, $rotation);
                }
            }
        }

        // timpa file lama
        $pdf->Output('F', Storage::disk('documents')->path($document->file_path));

        $document->update([
            'status' => 'signed',
        ]);

        return redirect()->route('dashboard.documents.index')
            ->with('success', 'Stampel diterapkan.');
    }

    /**
     * Normalisasi hanya PDF dengan compressed cross-reference ke PDF 1.4.
     */
    private function ensurePdfIsFpdiCompatible(string $pdfPath): void
    {
        if (! is_file($pdfPath)) {
            throw new RuntimeException('File PDF tidak ditemukan.');
        }

        try {
            (new Fpdi)->setSourceFile($pdfPath);

            return;
        } catch (CrossReferenceException) {
            // PDF akan dinormalisasi dengan Ghostscript di bawah.
        }

        $temporaryPath = dirname($pdfPath).DIRECTORY_SEPARATOR
            .pathinfo($pdfPath, PATHINFO_FILENAME)
            .'_normalized_'.bin2hex(random_bytes(6)).'.pdf';
        $backupPath = null;

        try {
            $process = new Process([
                $this->ghostscriptBinary(),
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-dNOPAUSE',
                '-dBATCH',
                '-dSAFER',
                '-dPDFSETTINGS=/prepress',
                '-sOutputFile='.$temporaryPath,
                $pdfPath,
            ]);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($temporaryPath) || filesize($temporaryPath) === 0) {
                $detail = trim($process->getErrorOutput() ?: $process->getOutput());
                throw new RuntimeException('PDF gagal dikonversi.'.($detail ? ' '.$detail : ''));
            }

            // Jangan mengganti file awal sebelum hasilnya terbukti bisa dibaca FPDI.
            (new Fpdi)->setSourceFile($temporaryPath);

            $backupPath = $pdfPath.'.backup_'.bin2hex(random_bytes(6));
            if (! rename($pdfPath, $backupPath)) {
                throw new RuntimeException('File PDF awal gagal diamankan sebelum konversi.');
            }

            if (! rename($temporaryPath, $pdfPath)) {
                rename($backupPath, $pdfPath);
                throw new RuntimeException('Hasil konversi PDF gagal disimpan.');
            }

            @unlink($backupPath);
            $backupPath = null;
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'PDF tidak kompatibel dan gagal dinormalisasi. Pastikan Ghostscript sudah terpasang: '
                .$exception->getMessage(),
                previous: $exception
            );
        } finally {
            if ($backupPath && is_file($backupPath) && ! is_file($pdfPath)) {
                @rename($backupPath, $pdfPath);
            }
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    private function ghostscriptBinary(): string
    {
        return env(
            'PDF_GHOSTSCRIPT_BINARY',
            PHP_OS_FAMILY === 'Windows' ? 'gswin64c.exe' : 'gs'
        );
    }
}
