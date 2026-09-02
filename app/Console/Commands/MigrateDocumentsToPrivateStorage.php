<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MigrateDocumentsToPrivateStorage extends Command
{
    protected $signature = 'documents:migrate-private
        {--delete-source : Hapus file publik hanya setelah salinan privat terverifikasi}
        {--dry-run : Tampilkan pekerjaan tanpa menyalin file}';

    protected $description = 'Copy legacy document and signature assets into private storage';

    public function handle(): int
    {
        $copied = 0;
        $skipped = 0;

        Document::query()->whereNotNull('file_path')->select(['id', 'file_path'])->chunkById(100, function ($documents) use (&$copied, &$skipped): void {
            foreach ($documents as $document) {
                $path = $this->validatedDocumentPath($document->file_path);
                if ($this->migrateFile('public', 'documents', $path)) {
                    $copied++;
                    $this->line("Document #{$document->id}: {$path}");
                } else {
                    $skipped++;
                }
            }
        });

        foreach ([
            'stampel-gpu.png',
            'stampel-ge.png',
            'old-stampel-gpu.png',
            'old-stampel-ge.png',
            'sign-arif.png',
            'sign-wahyu.png',
        ] as $asset) {
            if ($this->migratePublicAsset($asset)) {
                $copied++;
                $this->line("Signature asset: {$asset}");
            } else {
                $skipped++;
            }
        }

        $this->info("Selesai: {$copied} diproses, {$skipped} dilewati.");

        return self::SUCCESS;
    }

    private function migrateFile(string $sourceDisk, string $destinationDisk, string $path): bool
    {
        $source = Storage::disk($sourceDisk);
        $destination = Storage::disk($destinationDisk);

        if (! $source->exists($path)) {
            if ($destination->exists($path)) {
                return false;
            }

            $this->warn("File sumber tidak ditemukan: {$path}");

            return false;
        }

        if ($this->option('dry-run')) {
            return true;
        }

        $stream = $source->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException("Tidak dapat membaca {$path}.");
        }

        try {
            $destination->writeStream($path, $stream);
        } finally {
            fclose($stream);
        }

        $sourceHash = hash_file('sha256', $source->path($path));
        $destinationHash = hash_file('sha256', $destination->path($path));
        if (! hash_equals($sourceHash, $destinationHash)) {
            $destination->delete($path);
            throw new RuntimeException("Verifikasi salinan gagal untuk {$path}.");
        }

        if ($this->option('delete-source')) {
            $source->delete($path);
        }

        return true;
    }

    private function migratePublicAsset(string $asset): bool
    {
        $sourceDisk = Storage::disk('legacy-signature-assets');
        $destination = Storage::disk('signature-assets');

        if (! $sourceDisk->exists($asset)) {
            return false;
        }

        if ($this->option('dry-run')) {
            return true;
        }

        $stream = $sourceDisk->readStream($asset);
        if (! is_resource($stream)) {
            throw new RuntimeException("Tidak dapat membaca asset {$asset}.");
        }

        try {
            $destination->writeStream($asset, $stream);
        } finally {
            fclose($stream);
        }

        if (! hash_equals(hash_file('sha256', $sourceDisk->path($asset)), hash_file('sha256', $destination->path($asset)))) {
            $destination->delete($asset);
            throw new RuntimeException("Verifikasi asset gagal untuk {$asset}.");
        }

        if ($this->option('delete-source')) {
            $sourceDisk->delete($asset);
        }

        return true;
    }

    private function validatedDocumentPath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if (! str_starts_with($path, 'documents/') || str_contains($path, '../') || str_contains($path, "\0")) {
            throw new RuntimeException("Path dokumen tidak aman: {$path}");
        }

        return $path;
    }
}
