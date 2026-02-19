<?php

namespace App\Http\Requests\Publication;

use App\Models\Publication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('publication')->user_id;
    }

    public function rules(): array
    {
        return [
            'text' => ['sometimes', 'string', 'min:10', 'max:5000'],
            'type' => ['sometimes', 'integer', 'in:'.Publication::TYPE_CLIENT.','.Publication::TYPE_PROVIDER],
            'city_id' => ['sometimes', 'integer', 'exists:cities,id'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'mentions' => ['nullable', 'array'],
            'mentions.*' => ['integer', 'exists:users,id'],
            'media' => ['nullable', 'array'],
            'media.*' => ['file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime,video/x-msvideo,video/webm', 'max:51200'],
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => ['integer'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $publication = $this->route('publication');
                $currentCount = $publication->media()->count();
                $removeCount = count($this->input('remove_media', []));
                $addCount = count($this->file('media', []));

                $finalCount = $currentCount - $removeCount + $addCount;

                if ($finalCount > 10) {
                    $validator->errors()->add('media', 'A publicação não pode ter mais de 10 mídias. Atual: '.$currentCount.', removendo: '.$removeCount.', adicionando: '.$addCount.'.');
                }
            },
        ];
    }
}
