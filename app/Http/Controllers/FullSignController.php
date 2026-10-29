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
            'signRoutes.signer',
        ])->accessibleTo($request->user())
            ->where('status', 'signed')
            ->routingCompleted()
            ->latest()
            ->get();

        return view('dashboard.documents.index', compact('documents'));
    }
}
