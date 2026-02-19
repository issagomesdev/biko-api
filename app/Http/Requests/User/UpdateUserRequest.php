<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('user')->id;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:255', 'unique:users,username,'.$this->route('user')->id, 'regex:/^[a-zA-Z0-9._]+$/'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$this->route('user')->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:200'],
            'is_private' => ['nullable', 'boolean'],
            'city_id' => ['sometimes', 'integer', 'exists:cities,id'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'avatar' => ['nullable', 'image', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
            'cover' => ['nullable', 'image', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:10240'],
            'remove_avatar' => ['nullable', 'boolean'],
            'remove_cover' => ['nullable', 'boolean'],
        ];
    }
}
