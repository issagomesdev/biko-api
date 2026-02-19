<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username|regex:/^[a-zA-Z0-9._]+$/',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:200',
            'is_private' => 'nullable|boolean',
            'password' => 'required|string|min:8',
            'city_id' => 'required|exists:cities,id',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ];
    }
}
