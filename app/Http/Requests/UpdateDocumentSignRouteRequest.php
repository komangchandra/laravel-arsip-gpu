<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentSignRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageSignRouting', $this->route('document'));
    }

    public function rules(): array
    {
        return [
            'signers' => ['required', 'array', 'min:1'],
            'signers.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ];
    }
}
