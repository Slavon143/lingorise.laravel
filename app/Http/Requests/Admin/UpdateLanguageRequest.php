<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $language = $this->route('language');

        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('languages', 'code')->ignore($language?->id)],
            'name' => ['required', 'string', 'max:100'],
            'native_name' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'supports_translation' => ['boolean'],
            'supports_tts' => ['boolean'],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if (is_array($data) && isset($data['code'])) {
            $data['code'] = mb_strtolower(trim($data['code']));
        }

        return $data;
    }
}
