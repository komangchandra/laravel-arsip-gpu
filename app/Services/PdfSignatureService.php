<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class PdfSignatureService
{
    public function createSignedPdf(Document $document, array $signedPages): string
    {
        $disk = Storage::disk('documents');
        $source = $disk->path($document->file_path);
        if (! is_file($source) || strtolower(pathinfo($source, PATHINFO_EXTENSION)) !== 'pdf') {
            throw ValidationException::withMessages(['signed_pages' => 'Dokumen sumber harus berupa PDF yang valid.']);
        }

        $newPath = 'documents/signed_'.Str::uuid().'.pdf';
        $output = $disk->path($newPath);
        $temporaryImages = [];

        try {
            $pdf = new Fpdi;
            $pageCount = $pdf->setSourceFile($source);
            foreach ($signedPages as $page => $dataUrl) {
                if (! ctype_digit((string) $page) || (int) $page < 1 || (int) $page > $pageCount) {
                    throw new RuntimeException('Nomor halaman tanda tangan tidak valid.');
                }
            }

            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
                $pdf->useTemplate($template, 0, 0, $size['width'], $size['height']);
                if (isset($signedPages[$page])) {
                    [, $encoded] = explode(',', $signedPages[$page], 2);
                    $image = base64_decode($encoded, true);
                    if ($image === false || strlen($image) > 6 * 1024 * 1024) {
                        throw new RuntimeException('Gambar tanda tangan tidak valid atau terlalu besar.');
                    }
                    $info = @getimagesizefromstring($image);
                    if (! $info || ! in_array($info['mime'], ['image/png', 'image/jpeg'], true)) {
                        throw new RuntimeException('Format gambar tanda tangan tidak valid.');
                    }
                    $temporary = storage_path('app/sign_'.Str::uuid().($info['mime'] === 'image/png' ? '.png' : '.jpg'));
                    file_put_contents($temporary, $image);
                    $temporaryImages[] = $temporary;
                    $pdf->Image($temporary, 0, 0, $size['width'], $size['height']);
                }
            }
            $pdf->Output('F', $output);
            (new Fpdi)->setSourceFile($output);

            return $newPath;
        } catch (\Throwable $error) {
            $disk->delete($newPath);
            throw $error;
        } finally {
            foreach ($temporaryImages as $temporary) {
                @unlink($temporary);
            }
        }
    }
}
