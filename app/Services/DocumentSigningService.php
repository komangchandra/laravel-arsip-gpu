<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\SignRouteStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DocumentSigningService
{
    public function updateRouting(Document $document, User $creator, array $signerIds): void
    {
        DB::transaction(function () use ($document, $creator, $signerIds): void {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->id);

            if ($locked->routing_started_at !== null || ! in_array($locked->status, [DocumentStatus::Uploaded, DocumentStatus::Routing], true)) {
                throw ValidationException::withMessages(['signers' => 'Routing tidak dapat diubah setelah dimulai.']);
            }

            $locked->signRoutes()->delete();
            foreach (array_values($signerIds) as $index => $signerId) {
                $locked->signRoutes()->create([
                    'user_id' => $signerId,
                    'created_by' => $creator->id,
                    'sequence' => $index + 1,
                    'status' => SignRouteStatus::Pending,
                ]);
            }
            $locked->update(['status' => DocumentStatus::Routing]);
        });
    }

    public function start(Document $document): void
    {
        DB::transaction(function () use ($document): void {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->id);
            if ($locked->routing_started_at !== null || $locked->status !== DocumentStatus::Routing) {
                throw ValidationException::withMessages(['routing' => 'Routing ini sudah dimulai atau tidak valid.']);
            }

            $first = $locked->signRoutes()->lockForUpdate()->first();
            if (! $first) {
                throw ValidationException::withMessages(['routing' => 'Tambahkan minimal satu penandatangan.']);
            }

            $first->update(['status' => SignRouteStatus::Active, 'activated_at' => now()]);
            $locked->update([
                'status' => DocumentStatus::WaitingForSignatures,
                'routing_started_at' => now(),
                'current_sign_route_id' => $first->id,
            ]);
        });
    }

    public function recordSignature(Document $document, User $signer, string $newPath): string
    {
        return DB::transaction(function () use ($document, $signer, $newPath): string {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->id);
            $route = $locked->signRoutes()->whereKey($locked->current_sign_route_id)->lockForUpdate()->first();

            if ($locked->status !== DocumentStatus::WaitingForSignatures || ! $route || $route->user_id !== $signer->id || $route->status !== SignRouteStatus::Active) {
                abort(403, 'Bukan giliran Anda untuk menandatangani dokumen ini.');
            }

            $oldPath = $locked->file_path;
            $route->update(['status' => SignRouteStatus::Signed, 'signed_at' => now()]);
            $locked->signedBy()->syncWithoutDetaching([$signer->id]);

            $next = $locked->signRoutes()->where('sequence', '>', $route->sequence)->lockForUpdate()->first();
            if ($next) {
                $next->update(['status' => SignRouteStatus::Active, 'activated_at' => now()]);
                $locked->update(['file_path' => $newPath, 'current_sign_route_id' => $next->id]);
            } else {
                $locked->update([
                    'file_path' => $newPath,
                    'status' => DocumentStatus::Signed,
                    'current_sign_route_id' => null,
                    'signing_completed_at' => now(),
                ]);
            }

            return $oldPath;
        });
    }

    public function requestRevision(Document $document, User $signer, string $notes): void
    {
        DB::transaction(function () use ($document, $signer, $notes): void {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->id);
            $route = $locked->signRoutes()->whereKey($locked->current_sign_route_id)->lockForUpdate()->first();
            if (! $route || $route->user_id !== $signer->id || $route->status !== SignRouteStatus::Active) {
                abort(403);
            }
            $route->update([
                'status' => SignRouteStatus::RevisionRequested,
                'revision_requested_at' => now(),
                'notes' => $notes,
            ]);
            $locked->signRoutes()->where('status', SignRouteStatus::Pending)->update(['status' => SignRouteStatus::Cancelled]);
            $this->updateRevisionDocument($locked, ['current_sign_route_id' => null]);
        });
    }

    public function cancel(Document $document): void
    {
        DB::transaction(function () use ($document): void {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->id);
            $locked->signRoutes()->whereIn('status', [SignRouteStatus::Pending, SignRouteStatus::Active])->update(['status' => SignRouteStatus::Cancelled]);
            $locked->update(['status' => DocumentStatus::Cancelled, 'current_sign_route_id' => null]);
        });
    }

    public function markNeedsRevision(Document $document): void
    {
        DB::transaction(function () use ($document): void {
            $locked = Document::query()->lockForUpdate()->findOrFail($document->id);

            if (! in_array($locked->status, [DocumentStatus::Routing, DocumentStatus::WaitingForSignatures], true)) {
                throw ValidationException::withMessages([
                    'action' => 'Dokumen pada status ini tidak dapat ditandai untuk revisi.',
                ]);
            }

            $locked->signRoutes()
                ->whereIn('status', [SignRouteStatus::Pending, SignRouteStatus::Active])
                ->update(['status' => SignRouteStatus::Cancelled]);

            $this->updateRevisionDocument($locked, ['current_sign_route_id' => null]);
        });
    }

    public function updateRevisionDocument(Document $document, array $attributes = []): void
    {
        $oldPath = $attributes['file_path'] ?? $document->file_path;
        $disk = Storage::disk('documents');
        if ($oldPath && ! $disk->exists($oldPath)) {
            $disk = Storage::disk('public');
        }

        $newPath = $oldPath;
        if ($oldPath && $disk->exists($oldPath) && ! str_starts_with(basename($oldPath), 'revisi_')) {
            $newPath = dirname($oldPath).'/revisi_'.basename($oldPath);
            if ($disk->exists($newPath)) {
                $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
                $newPath = dirname($oldPath).'/revisi_'.pathinfo($oldPath, PATHINFO_FILENAME)
                    .'_'.Str::uuid().($extension ? '.'.$extension : '');
            }

            if (! $disk->move($oldPath, $newPath)) {
                throw new RuntimeException('File gagal diubah namanya untuk revisi.');
            }
        }

        try {
            $document->update(array_merge($attributes, [
                'status' => DocumentStatus::NeedsRevision,
                'file_path' => $newPath,
            ]));
        } catch (\Throwable $error) {
            if ($newPath !== $oldPath) {
                $disk->move($newPath, $oldPath);
            }

            throw $error;
        }
    }
}
