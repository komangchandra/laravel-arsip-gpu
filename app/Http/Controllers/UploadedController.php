<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class UploadedController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with([
            'creator',
            'category',
            'checkedBy',
            'signedBy'
        ])
        ->where('status', 'uploaded'); // ⬅️ WAJIB ini

        /*
        |--------------------------------------------------------------------------
        | FILTER TANGGAL (opsional tetap boleh dipakai)
        |--------------------------------------------------------------------------
        */
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $documents = $query->oldest()->get();

        return view('dashboard.documents.index', compact('documents'));
    }
}
