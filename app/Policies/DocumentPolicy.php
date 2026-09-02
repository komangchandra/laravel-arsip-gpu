<?php

namespace App\Policies;

use App\Enums\DocumentStatus;
use App\Enums\SignRouteStatus;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Document $document): bool
    {
        return $this->canAccessFile($user, $document);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'staff', 'staff-haul']);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->isUploader($user, $document)
            || $user->hasRole('super-admin')
            || $user->hasPermissionTo('documents.update');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasRole('super-admin')
            || ($this->isUploader($user, $document) && $document->routing_started_at === null);
    }

    public function download(User $user, Document $document): bool
    {
        return $this->canAccessFile($user, $document);
    }

    public function annotate(User $user, Document $document): bool
    {
        return $user->hasRole('super-admin') && $this->view($user, $document);
    }

    public function stamp(User $user, Document $document): bool
    {
        return $user->hasAnyRole(['super-admin', 'staff'])
            && ($this->isUploader($user, $document)
                || $this->isAssignedSigner($user, $document)
                || $user->hasRole('super-admin'))
            && in_array($document->status, [
                DocumentStatus::Signed,
                DocumentStatus::Stamped,
            ], true);
    }

    public function archive(User $user, Document $document): bool
    {
        return ($this->isUploader($user, $document)
                || $user->hasRole('super-admin')
                || $user->hasPermissionTo('documents.update'))
            && in_array($document->status, [
                DocumentStatus::Signed,
                DocumentStatus::Stamped,
            ], true);
    }

    public function manageSignRouting(User $user, Document $document): bool
    {
        return $document->created_by === $user->id
            && $document->routing_started_at === null
            && in_array($document->status, [
                DocumentStatus::Uploaded,
                DocumentStatus::Routing,
            ], true);
    }

    public function sign(User $user, Document $document): bool
    {
        return $document->status === DocumentStatus::WaitingForSignatures
            && $document->signRoutes()
                ->where('user_id', $user->id)
                ->where('status', SignRouteStatus::Active->value)
                ->exists();
    }

    public function signTempel(User $user, Document $document): bool
    {
        return $user->hasRole('ktt') && $this->sign($user, $document);
    }

    public function startSignRouting(User $user, Document $document): bool
    {
        return $this->manageSignRouting($user, $document) && $document->signRoutes()->exists();
    }

    public function cancelSignRouting(User $user, Document $document): bool
    {
        return $document->created_by === $user->id
            && in_array($document->status, [DocumentStatus::Routing, DocumentStatus::WaitingForSignatures], true);
    }

    public function requestRevision(User $user, Document $document): bool
    {
        return $this->sign($user, $document);
    }

    private function isUploader(User $user, Document $document): bool
    {
        return $document->created_by === $user->id;
    }

    private function isAssignedSigner(User $user, Document $document): bool
    {
        return $document->signRoutes()->where('user_id', $user->id)->exists();
    }

    private function canAccessFile(User $user, Document $document): bool
    {
        return $document->status === DocumentStatus::Archived
            || $this->isUploader($user, $document)
            || $this->isAssignedSigner($user, $document)
            || $user->hasRole('super-admin')
            || strcasecmp($user->email, 'admin.engineering@gorbyputrautama.com') === 0;
    }
}
