<?php

namespace App\Http\Requests\Publication;

use App\Models\Publication;
use Illuminate\Foundation\Http\FormRequest;

class StorePublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'min:10', 'max:5000'],
            'type' => ['required', 'integer', 'in:'.Publication::TYPE_CLIENT.','.Publication::TYPE_PROVIDER],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'mentions' => ['nullable', 'array'],
            'mentions.*' => ['integer', 'exists:users,id'],
            'media' => ['nullable', 'array', 'max:10'],
            'media.*' => ['file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime,video/x-msvideo,video/webm', 'max:51200'],
        ];
    }
}
