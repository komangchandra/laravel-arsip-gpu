<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestDocumentRevisionRequest;
use App\Http\Requests\StoreDocumentSignatureRequest;
use App\Models\Document;
use App\Pdf\RotatableFpdi;
use App\Services\DocumentSigningService;
use App\Services\PdfSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DocumentSignController extends Controller
{
    public function show(Document $document): View
    {
        $this->authorize('sign', $document);

        return view('dashboard.documents.sign', compact('document'));
    }

    public function store(StoreDocumentSignatureRequest $request, Document $document, PdfSignatureService $pdfs, DocumentSigningService $workflow): RedirectResponse
    {
        $newPath = $pdfs->createSignedPdf($document, $request->validated('signed_pages'));
        try {
            $oldPath = $workflow->recordSignature($document, $request->user(), $newPath);
        } catch (\Throwable $error) {
            Storage::disk('documents')->delete($newPath);
            throw $error;
        }
        if ($oldPath !== $newPath) {
            Storage::disk('documents')->delete($oldPath);
        }

        return redirect()->route('dashboard.signing-inbox.index')->with('success', 'Dokumen berhasil ditandatangani.');
    }

    public function showStamp(Document $document): View
    {
        $this->authorize('signTempel', $document);

        return view('dashboard.documents.sign-tempel', compact('document'));
    }

    public function storeStamp(Request $request, Document $document, DocumentSigningService $workflow): RedirectResponse
    {
        $this->authorize('signTempel', $document);

        $decoded = json_decode($request->string('stamps')->toString(), true);
        if (! is_array($decoded) || collect($decoded)->flatten(1)->isEmpty()) {
            throw ValidationException::withMessages(['stamps' => 'Tambahkan minimal satu sign tempel.']);
        }

        $stamps = Validator::make(['stamps' => $decoded], [
            'stamps' => ['required', 'array'],
            'stamps.*' => ['required', 'array'],
            'stamps.*.*.type' => ['required', 'in:gpu,ge'],
            'stamps.*.*.x_ratio' => ['required', 'numeric', 'between:0,1'],
            'stamps.*.*.y_ratio' => ['required', 'numeric', 'between:0,1'],
            'stamps.*.*.width_ratio' => ['required', 'numeric', 'gt:0', 'max:1'],
            'stamps.*.*.height_ratio' => ['required', 'numeric', 'gt:0', 'max:1'],
            'stamps.*.*.rotation' => ['nullable', 'numeric', 'between:-360,360'],
        ])->validate()['stamps'];

        $disk = Storage::disk('documents');
        $newPath = 'documents/signed-tempel-'.Str::uuid().'.pdf';

        try {
            $pdf = new RotatableFpdi;
            $pageCount = $pdf->setSourceFile($disk->path($document->file_path));
            $stampMap = ['gpu' => 'sign-wahyu.png', 'ge' => 'sign-arif.png'];

            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);

                foreach ($stamps[$page] ?? [] as $stamp) {
                    $asset = Storage::disk('signature-assets')->path($stampMap[$stamp['type']]);
                    if (! is_file($asset)) {
                        throw ValidationException::withMessages(['stamps' => 'Aset sign tempel tidak ditemukan.']);
                    }

                    $pdf->rotatedImage(
                        $asset,
                        (float) $stamp['x_ratio'] * $size['width'],
                        (float) $stamp['y_ratio'] * $size['height'],
                        (float) $stamp['width_ratio'] * $size['width'],
                        (float) $stamp['height_ratio'] * $size['height'],
                        (float) ($stamp['rotation'] ?? 0),
                    );
                }
            }

            $pdf->Output('F', $disk->path($newPath));
            $oldPath = $workflow->recordSignature($document, $request->user(), $newPath);
        } catch (\Throwable $error) {
            $disk->delete($newPath);
            throw $error;
        }

        if ($oldPath !== $newPath) {
            $disk->delete($oldPath);
        }

        return redirect()->route('dashboard.signing-inbox.index')->with('success', 'Sign tempel berhasil diterapkan.');
    }

    public function requestRevision(RequestDocumentRevisionRequest $request, Document $document, DocumentSigningService $service): RedirectResponse
    {
        $service->requestRevision($document, $request->user(), $request->validated('notes'));

        return redirect()->route('dashboard.signing-inbox.index')->with('success', 'Permintaan revisi dikirim dan routing dihentikan.');
    }
}
