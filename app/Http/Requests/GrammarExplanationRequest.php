<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GrammarExplanationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'min:1', 'max:1500'],
            'context' => ['nullable', 'string', 'max:2000'],
            'source_language' => ['required', 'string', 'max:12'],
            'target_language' => ['nullable', 'string', 'max:12'],
        ];
    }

    public function authorize(): bool
    {
        return (bool) $this->user();
    }
}
