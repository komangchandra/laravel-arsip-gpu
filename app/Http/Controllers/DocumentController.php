<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentApproval;
use Spatie\Permission\Models\Role;
// use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $documents = Document::with(['creator', 'checker', 'category'])->get();
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
        $document->created_by = auth()->id();
        $document->category_id = $validated['category_id'] ?? null;
        $document->save();

        $roles = [
            'staff-haul',
            'staff',
            'sr-staff-haul',
            'sr-staff',
            'ktt',
            'manager',
        ];

        foreach ($roles as $roleName) {
            DocumentApproval::create([
                'document_id' => $document->id,
                'user_id' => Role::where('name', $roleName)->first()->users()->first()->id,
                'role_name' => $roleName,
                'status' => 'pending',
            ]);
        }

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
            'status' => 'required|in:uploaded,checked,in_approval,signed,archived',
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

        $validated['checked_by'] = auth()->id();

        $document->update($validated);

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
}
