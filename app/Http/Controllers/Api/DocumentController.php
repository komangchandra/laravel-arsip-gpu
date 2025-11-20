<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $document->update([
            'status' => 'Signed',
            'signed_by_user_id' => $user->id, 
            'signed_at' => now(), 
        ]);
        
        return response()->json([
            'message' => 'Dokumen berhasil ditandatangani.', 
            'document' => $document->fresh()
        ]);
    }
}
