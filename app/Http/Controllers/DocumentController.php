<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use setasign\Fpdi\Fpdi;
use Intervention\Image\Drivers\Gd\Driver;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $documents = Document::with(['creator', 'category'])->get();
        return view('dashboard.documents.index', compact('documents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        return view('dashboard.documents.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'file_path' => 'required|file|mimes:pdf,doc,docx',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $path = $request->file('file_path')->store('documents', 'public');

        $document = new Document();
        $document->title = $validated['title'];
        $document->file_path = $path;
        $document->status = 'uploaded';
        $document->created_by = Auth::id();
        $document->category_id = $validated['category_id'] ?? null;
        $document->save();

        return redirect()->route('dashboard.documents.index')->with('success', 'Document uploaded successfully.');
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
        $categories = Category::all();
        return view('dashboard.documents.edit', compact(['document', 'categories']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'file_path' => 'nullable|file|mimes:pdf,doc,docx',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'required',
        ]);

        // Update file jika ada file baru
        if ($request->hasFile('file_path')) {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $validated['file_path'] = $request->file('file_path')->store('documents', 'public');
        } else {
            $validated['file_path'] = $document->file_path;
        }

        // Update document (tanpa checked_by)
        $document->update([
            'title' => $validated['title'],
            'file_path' => $validated['file_path'],
            'category_id' => $validated['category_id'] ?? $document->category_id,
            'status' => $validated['status'],
        ]);

        // Tambahkan user ke pivot checked_by
        $document->checkedBy()->syncWithoutDetaching([Auth::id()]);

        return redirect()
            ->route('dashboard.documents.index')
            ->with('success', 'Document updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document)
    {
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
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
        $originalPdfPath = storage_path('app/public/' . $document->file_path);

        // Output PDF
        $newFilePath = 'documents/signed_' . time() . '.pdf';
        $outputPdfPath = storage_path('app/public/' . $newFilePath);

        // Start FPDI
        $pdf = new Fpdi();
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
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        // ================================
        // UPDATE PATH DI TABLE DOCUMENTS
        // ================================
        $document->update([
            'file_path' => $newFilePath,
            'status' => 'signed'
        ]);

        // ================================
        // PIVOT — insert siapa yang tanda tangan
        // ================================
        $document->signedBy()->attach(Auth::id());

        return redirect()
            ->route('dashboard.documents.index')
            ->with('success', 'Document signed successfully.');
    }


}
