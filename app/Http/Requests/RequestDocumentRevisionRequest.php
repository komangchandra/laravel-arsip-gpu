<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestDocumentRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('requestRevision', $this->route('document'));
    }

    public function rules(): array
    {
        return ['notes' => ['required', 'string', 'max:2000']];
    }
}
