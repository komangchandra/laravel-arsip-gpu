<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentFileController extends Controller
{
    public function preview(Document $document): BinaryFileResponse
    {
        $this->authorize('view', $document);

        [$disk, $path] = $this->locateDocument($document);

        $response = response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$this->safeFilename($document).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $this->makePrivate($response);

        return $response;
    }

    public function download(Document $document): BinaryFileResponse
    {
        $this->authorize('download', $document);

        [$disk, $path] = $this->locateDocument($document);

        $response = response()->download($disk->path($path), $this->safeFilename($document), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $this->makePrivate($response);

        return $response;
    }

    public function signatureAsset(Document $document, string $asset): BinaryFileResponse
    {
        $stampAssets = ['stampel-gpu.png', 'stampel-ge.png'];
        $signAssets = ['sign-wahyu.png', 'sign-arif.png'];

        if (in_array($asset, $stampAssets, true)) {
            $this->authorize('stamp', $document);
        } elseif (in_array($asset, $signAssets, true)) {
            $this->authorize('signTempel', $document);
        } else {
            abort(404);
        }

        $disk = Storage::disk('signature-assets');
        abort_unless($disk->exists($asset), 404);

        $response = response()->file($disk->path($asset), [
            'Content-Type' => 'image/png',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $this->makePrivate($response);

        return $response;
    }

    /** @return array{FilesystemAdapter, string} */
    private function locateDocument(Document $document): array
    {
        abort_if(blank($document->file_path), 404);

        $private = Storage::disk('documents');
        if ($private->exists($document->file_path)) {
            return [$private, $document->file_path];
        }

        // Transitional fallback for records not migrated yet.
        $legacy = Storage::disk('public');
        abort_unless($legacy->exists($document->file_path), 404);

        return [$legacy, $document->file_path];
    }

    private function safeFilename(Document $document): string
    {
        $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION)) ?: 'pdf';
        $title = preg_replace('/[^A-Za-z0-9._-]+/', '_', $document->title) ?: 'document';

        return trim($title, '._-').'.'.$extension;
    }

    private function makePrivate(BinaryFileResponse $response): void
    {
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->addCacheControlDirective('no-cache');
    }
}
