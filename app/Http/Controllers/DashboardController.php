<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentApprovalLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        /*
        |--------------------------------------------------------------------------
        | 1. Total Document Yang Sudah Arsip
        |--------------------------------------------------------------------------
        */
        $documents = Document::where('status', 'archived')->count();

        /*
        |--------------------------------------------------------------------------
        | 2. Dokumen yang sudah full sign bulan ini
        |--------------------------------------------------------------------------
        */
        $fullThisMonth = Document::where('status', 'archived')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 3. Total Dokumen yang saya upload (created_by = user)
        |--------------------------------------------------------------------------
        */
        $myDocuments = Document::where('created_by', $userId)->count();

        /*
        |--------------------------------------------------------------------------
        | 4. Baru diupload hari ini
        |--------------------------------------------------------------------------
        */
        $recentActivities = Document::where('status', 'uploaded')
            ->whereDate('created_at', now())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard.index', compact(
            'documents',
            'fullThisMonth',
            'myDocuments',
            'recentActivities'
        ));
    }

}
