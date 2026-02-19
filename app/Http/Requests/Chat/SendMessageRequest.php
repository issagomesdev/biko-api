<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $this->user()->id === $conversation->user_one_id
            || $this->user()->id === $conversation->user_two_id;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'reply_to_id' => ['nullable', 'integer', 'exists:messages,id'],
        ];
    }
}
