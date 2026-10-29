<?php

namespace App\Http\Controllers;

use App\Models\Document;
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
        $monthStart = now()->startOfMonth();
        $fullThisMonth = Document::query()->routingCompleted()
            ->whereIn('status', ['signed', 'stamped', 'archived'])
            ->where('signing_completed_at', '>=', $monthStart)
            ->where('signing_completed_at', '<', $monthStart->copy()->addMonth())
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
