<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if (is_array($validated) && array_key_exists('email', $validated)) {
            $validated['email'] = mb_strtolower(trim($validated['email']));
        }

        if (is_array($validated) && array_key_exists('name', $validated)) {
            $validated['name'] = trim($validated['name']);
        }

        return $validated;
    }
}
