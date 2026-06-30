<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShadowingAttemptRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page_number' => ['required', 'integer', 'min:1'],
            'word_index_start' => ['required', 'integer', 'min:0'],
            'word_index_end' => ['required', 'integer', 'min:0', 'gte:word_index_start'],
            'sentence_hash' => ['required', 'string', 'max:64'],
            'self_rating' => ['nullable', 'string', 'in:easy,okay,difficult,almost_correct,good'],
        ];
    }

    public function authorize(): bool
    {
        return (bool) $this->user();
    }
}
