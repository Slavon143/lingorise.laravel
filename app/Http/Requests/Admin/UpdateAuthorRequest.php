<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $author = $this->route('author');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('authors', 'slug')->ignore($author?->id)],
            'bio' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'country' => ['nullable', 'string', 'max:100'],
            'birth_year' => ['nullable', 'integer', 'min:1800', 'max:' . date('Y')],
            'death_year' => ['nullable', 'integer', 'min:1800', 'max:' . date('Y')],
        ];
    }
}
