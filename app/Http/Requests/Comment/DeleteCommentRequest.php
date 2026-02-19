<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('comment')->user_id;
    }

    public function rules(): array
    {
        return [];
    }
}
