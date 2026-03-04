<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class FullSignController extends Controller
{
    public function index()
    {
        $documents = Document::with([
                'creator',
                'category',
                'checkedBy',
                'signedBy'
            ])
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
