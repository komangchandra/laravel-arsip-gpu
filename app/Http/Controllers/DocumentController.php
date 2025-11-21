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
        $documents = Document::with(['creator', 'category'])->where('status', '!=', 'archived')->get();
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

    public function stamp(Document $document)
    {
        return view('dashboard.documents.stamp', compact('document'));
    }

    public function stampStore(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        $stampsData = json_decode($request->stamps, true);
        if (!$stampsData) {
            return back()->with('error', 'Tidak ada stampel untuk disimpan.');
        }

        $original = storage_path('app/public/' . $document->file_path);

        $pdf = new \setasign\Fpdi\Fpdi();
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
                    $stampPath = public_path("images/stampel-$type.png");
                    if (!file_exists($stampPath)) continue;

                    // Konversi koordinat relatif ke PDF asli
                    $x = $s['x_ratio'] * $size['width'];
                    $y = $s['y_ratio'] * $size['height'];
                    $w = $s['width_ratio'] * $size['width'];
                    $h = $s['height_ratio'] * $size['height'];

                    $pdf->Image($stampPath, $x, $y, $w, $h, '', '', '', false, 300, '', false, false, 0, $s['rotation']);
                }
            }
        }

        // timpa file lama
        $pdf->Output('F', storage_path('app/public/' . $document->file_path));

        $document->update([
            'status' => 'stamped'
        ]);

        return redirect()->route('dashboard.documents.index')
            ->with('success', 'Stamp diterapkan.');
    }


}
