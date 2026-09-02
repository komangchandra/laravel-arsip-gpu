<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class FullSignController extends Controller
{
    public function index(Request $request)
    {
        $documents = Document::with([
            'creator',
            'category',
            'checkedBy',
            'signedBy',
        ])->accessibleTo($request->user())
            ->where('status', 'signed')
            ->whereHas('signedBy', function ($query) {
                $query->where('email', 'ferry.juanda@gorbyputrautama.com');
            })
            ->latest()
            ->get();

        // dd($documents);

        return view('dashboard.documents.index', compact('documents'));
    }
}
