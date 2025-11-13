<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $guarded = [];

    // User created the document
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // User who checked the document
    public function checker()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
