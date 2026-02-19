<?php

namespace App\Http\Requests\Collection;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('collection')->user_id
            && ! $this->route('collection')->is_default;
    }

    public function rules(): array
    {
        return [];
    }
}
