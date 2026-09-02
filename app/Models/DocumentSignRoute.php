<?php

namespace App\Models;

use App\Enums\SignRouteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSignRoute extends Model
{
    protected $fillable = [
        'document_id',
        'user_id',
        'created_by',
        'sequence',
        'status',
        'activated_at',
        'signed_at',
        'revision_requested_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => SignRouteStatus::class,
            'activated_at' => 'datetime',
            'signed_at' => 'datetime',
            'revision_requested_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
