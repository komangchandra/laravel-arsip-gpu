<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $documents = Document::query()->accessibleTo($request->user())->get();

        return response()->json($documents);
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        $this->authorize('view', $document);
        $disk = Storage::disk('documents')->exists($document->file_path) ? 'documents' : 'public';

        if (! $document->file_path || ! Storage::disk($disk)->exists($document->file_path)) {
            return response()->json(['message' => 'File tidak ditemukan.'], 404);
        }

        $path = Storage::disk($disk)->path($document->file_path);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($document->file_path).'"',
        ]);
    }
}
