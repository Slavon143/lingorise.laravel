<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'language_locale' => ['required', 'string', 'max:10'],
            'level' => ['required', 'in:A1,A2,B1,B2,C1,C2'],
            'visibility' => ['required', 'in:private,public'],
            'content' => ['nullable', 'string', 'max:2000000'],
            'book_file' => ['nullable', 'file', 'max:10240', 'extensions:txt,epub'],
            'cover_file' => ['nullable', 'file', 'max:5120', 'extensions:jpg,jpeg,png,webp'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('content') && ! $this->hasFile('book_file')) {
                    $validator->errors()->add('content', 'Paste text or upload a TXT/EPUB file.');
                }

                if ($this->filled('content') && $this->hasFile('book_file')) {
                    $validator->errors()->add('book_file', 'Choose either pasted text or a file, not both.');
                }
            },
        ];
    }
}
