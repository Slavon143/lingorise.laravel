<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimplificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'min:1', 'max:4000'],
            'source_language' => ['required', 'string', 'max:12'],
            'target_level' => ['required', 'string', 'in:A1,A2,B1,B2,C1'],
            'preserve_style' => ['nullable', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return (bool) $this->user();
    }
}
