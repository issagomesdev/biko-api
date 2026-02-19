<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('message')->sender_id;
    }

    public function rules(): array
    {
        return [];
    }
}
