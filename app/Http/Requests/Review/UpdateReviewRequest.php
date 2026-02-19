<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('review')->reviewer_id;
    }

    public function rules(): array
    {
        $review = $this->route('review');
        $currentMediaCount = $review->media()->count();
        $removeCount = count($this->input('remove_media', []));
        $maxNew = 5 - $currentMediaCount + $removeCount;

        $rules = [
            'comment' => ['sometimes', 'string', 'min:1', 'max:2000'],
            'media' => ['nullable', 'array', 'max:' . max(0, $maxNew)],
            'media.*' => ['file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime,video/x-msvideo,video/webm', 'max:51200'],
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => ['integer', 'exists:media,id'],
        ];

        // Only root reviews can update stars
        if (is_null($review->parent_id)) {
            $rules['stars'] = ['sometimes', 'integer', 'min:1', 'max:5'];
        }

        return $rules;
    }
}
