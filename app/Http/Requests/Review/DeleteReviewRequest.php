<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class DeleteReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('review')->reviewer_id;
    }

    public function rules(): array
    {
        return [];
    }
}
