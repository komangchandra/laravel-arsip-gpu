<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'routing_started_at' => 'datetime',
            'signing_completed_at' => 'datetime',
        ];
    }

    // User created the document
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // User who checked the document
    public function checkedBy()
    {
        return $this->belongsToMany(User::class, 'document_checkeds');
    }

    public function signedBy()
    {
        return $this->belongsToMany(User::class, 'document_signeds');
    }

    // Category of the document
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function signRoutes(): HasMany
    {
        return $this->hasMany(DocumentSignRoute::class)->orderBy('sequence');
    }

    public function currentSignRoute(): BelongsTo
    {
        return $this->belongsTo(DocumentSignRoute::class, 'current_sign_route_id');
    }

    public function revisionOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'revision_of_document_id');
    }

    public function isAwaitingSignatureFrom(User $user): bool
    {
        return $this->status === DocumentStatus::WaitingForSignatures
            && $this->signRoutes()->where('user_id', $user->id)->where('status', 'active')->exists();
    }

    public function signedRoutesCount(): int
    {
        return $this->signRoutes()->where('status', 'signed')->count();
    }

    public function signRoutesCount(): int
    {
        return $this->signRoutes()->count();
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        // Progress dan metadata dokumen transparan untuk seluruh user terautentikasi.
        // Akses isi file tetap diputuskan secara terpisah oleh DocumentPolicy.
        return $query;
    }
}
