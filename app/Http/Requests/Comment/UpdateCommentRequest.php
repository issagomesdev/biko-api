<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('comment')->user_id;
    }

    public function rules(): array
    {
        return [
            'comment'        => ['required', 'string', 'min:1', 'max:2000'],
            'media'          => ['nullable', 'array', 'max:5'],
            'media.*'        => ['file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime,video/x-msvideo,video/webm', 'max:51200'],
            'remove_media'   => ['nullable', 'array'],
            'remove_media.*' => ['integer'],
        ];
    }
}
