<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDocumentSignRouteRequest;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentSigningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DocumentSignRouteController extends Controller
{
    public function edit(Document $document): View
    {
        $this->authorize('manageSignRouting', $document);
        $document->load('signRoutes.signer');
        $users = User::query()
            ->with('roles')
            ->where('email', '!=', 'komangchandraaa1@gmail.com')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', [
                    'manager',
                    'ktt',
                    'sr-staff',
                    'sr-staff-haul',
                ]);
            })
            ->orderBy('id', 'asc')
            ->get();

        return view('dashboard.documents.sign-routing', compact('document', 'users'));
    }

    public function update(UpdateDocumentSignRouteRequest $request, Document $document, DocumentSigningService $service): RedirectResponse
    {
        $service->updateRouting($document, $request->user(), $request->validated('signers'));

        return back()->with('success', 'Draft routing berhasil disimpan.');
    }

    public function start(Document $document, DocumentSigningService $service): RedirectResponse
    {
        $this->authorize('startSignRouting', $document);
        $service->start($document);

        return redirect()->route('dashboard.documents.index')->with('success', 'Routing tanda tangan dimulai.');
    }

    public function cancel(Document $document, DocumentSigningService $service): RedirectResponse
    {
        $this->authorize('cancelSignRouting', $document);
        $service->cancel($document);

        return back()->with('success', 'Routing tanda tangan dibatalkan.');
    }
}
