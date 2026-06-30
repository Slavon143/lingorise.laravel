<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContextExplanationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'selected_text' => ['required', 'string', 'min:1', 'max:300'],
            'context' => ['required', 'string', 'max:1500'],
            'source_language' => ['required', 'string', 'max:12'],
            'target_language' => ['nullable', 'string', 'max:12'],
        ];
    }

    public function authorize(): bool
    {
        return (bool) $this->user();
    }
}
