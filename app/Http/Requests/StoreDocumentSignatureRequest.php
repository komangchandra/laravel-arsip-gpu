<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sign', $this->route('document'));
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->signed_pages)) {
            $this->merge(['signed_pages' => json_decode($this->signed_pages, true)]);
        }
    }

    public function rules(): array
    {
        return [
            'signed_pages' => ['required', 'array', 'min:1'],
            'signed_pages.*' => ['required', 'string', 'max:8388608', 'regex:/^data:image\/(png|jpeg);base64,/'],
        ];
    }
}
