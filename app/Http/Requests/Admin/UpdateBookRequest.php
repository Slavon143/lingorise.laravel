<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('books', 'slug')->ignore($book?->id)],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'author_id' => ['nullable', 'integer', 'exists:authors,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id', function ($attribute, $value, $fail): void {
                if ($value && ! \App\Models\Language::where('id', $value)->where('is_active', true)->exists()) {
                    $fail('The selected language is not active.');
                }
            }],
            'difficulty' => ['nullable', 'string', Rule::in(['beginner', 'elementary', 'intermediate', 'upper_intermediate', 'advanced'])],
            'access_type' => ['required', Rule::in(['public', 'premium', 'private'])],
            'status' => ['required', Rule::in(['draft', 'ready', 'published', 'archived'])],
            'cover' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_featured' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if (is_array($data)) {
            $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        }

        return $data;
    }
}
