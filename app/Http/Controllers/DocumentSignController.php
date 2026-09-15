<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestDocumentRevisionRequest;
use App\Http\Requests\StoreDocumentSignatureRequest;
use App\Models\Document;
use App\Pdf\RotatableFpdi;
use App\Services\DocumentSigningService;
use App\Services\PdfSignatureService;
use App\Support\PersonalSignatureAssets;
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

    public function showTempel(Document $document): View
    {
        $this->authorize('tempelSignature', $document);
        $assetConfiguration = PersonalSignatureAssets::forUser(request()->user());

        return view('dashboard.documents.tempel', compact('document', 'assetConfiguration'));
    }

    public function storeTempel(Request $request, Document $document, DocumentSigningService $workflow): RedirectResponse
    {
        $this->authorize('tempelSignature', $document);
        $assetConfiguration = PersonalSignatureAssets::forUser($request->user());

        return $this->storePlacedImages(
            $request,
            $document,
            $workflow,
            $assetConfiguration['assets'],
            'tanda tangan atau paraf',
            'tempel',
        );
    }

    /**
     * @param  array<string, string>  $assetMap
     */
    private function storePlacedImages(
        Request $request,
        Document $document,
        DocumentSigningService $workflow,
        array $assetMap,
        string $label,
        string $outputPrefix,
    ): RedirectResponse {
        $decoded = json_decode($request->string('stamps')->toString(), true);
        if (! is_array($decoded) || collect($decoded)->flatten(1)->isEmpty()) {
            throw ValidationException::withMessages(['stamps' => "Tambahkan minimal satu {$label}."]);
        }

        $stamps = Validator::make(['stamps' => $decoded], [
            'stamps' => ['required', 'array', 'max:100'],
            'stamps.*' => ['required', 'array', 'max:50'],
            'stamps.*.*.type' => ['required', 'in:'.implode(',', array_keys($assetMap))],
            'stamps.*.*.x_ratio' => ['required', 'numeric', 'between:0,1'],
            'stamps.*.*.y_ratio' => ['required', 'numeric', 'between:0,1'],
            'stamps.*.*.width_ratio' => ['required', 'numeric', 'gt:0', 'max:1'],
            'stamps.*.*.height_ratio' => ['required', 'numeric', 'gt:0', 'max:1'],
            'stamps.*.*.rotation' => ['nullable', 'numeric', 'between:-360,360'],
        ])->validate()['stamps'];

        if (collect($stamps)->sum(fn (array $placements): int => count($placements)) > 100) {
            throw ValidationException::withMessages(['stamps' => 'Maksimal 100 gambar dapat ditempel dalam satu dokumen.']);
        }

        foreach ($stamps as $placements) {
            foreach ($placements as $stamp) {
                if ((float) $stamp['x_ratio'] + (float) $stamp['width_ratio'] > 1
                    || (float) $stamp['y_ratio'] + (float) $stamp['height_ratio'] > 1) {
                    throw ValidationException::withMessages(['stamps' => 'Posisi atau ukuran gambar melewati batas halaman.']);
                }
            }
        }

        $disk = Storage::disk('documents');
        $newPath = "documents/{$outputPrefix}-".Str::uuid().'.pdf';

        try {
            $pdf = new RotatableFpdi;
            $pageCount = $pdf->setSourceFile($disk->path($document->file_path));

            foreach (array_keys($stamps) as $pageNumber) {
                if (filter_var($pageNumber, FILTER_VALIDATE_INT) === false
                    || (int) $pageNumber < 1
                    || (int) $pageNumber > $pageCount) {
                    throw ValidationException::withMessages(['stamps' => 'Nomor halaman tidak valid.']);
                }
            }

            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);

                foreach ($stamps[$page] ?? [] as $stamp) {
                    $asset = Storage::disk('signature-assets')->path($assetMap[$stamp['type']]);
                    if (! is_file($asset)) {
                        throw ValidationException::withMessages(['stamps' => "Aset {$label} tidak ditemukan."]);
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

        return redirect()->route('dashboard.signing-inbox.index')->with('success', ucfirst($label).' berhasil diterapkan.');
    }

    public function requestRevision(RequestDocumentRevisionRequest $request, Document $document, DocumentSigningService $service): RedirectResponse
    {
        $service->requestRevision($document, $request->user(), $request->validated('notes'));

        return redirect()->route('dashboard.signing-inbox.index')->with('success', 'Permintaan revisi dikirim dan routing dihentikan.');
    }
}
