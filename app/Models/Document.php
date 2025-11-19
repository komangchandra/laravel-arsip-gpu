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
}
