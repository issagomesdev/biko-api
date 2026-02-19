<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class FilterNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:like,comment,follow,follow_request,mention,review,review_reply,comment_reply,message'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
