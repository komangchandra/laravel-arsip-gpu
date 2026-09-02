<?php

namespace App\Http\Controllers;

use App\Enums\SignRouteStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SigningInboxController extends Controller
{
    public function index(Request $request): View
    {
        $routes = $request->user()->assignedSignRoutes()
            ->where('status', SignRouteStatus::Active)
            ->with(['document.creator', 'document.category'])
            ->latest('activated_at')
            ->get();

        return view('dashboard.signing-inbox.index', compact('routes'));
    }
}
