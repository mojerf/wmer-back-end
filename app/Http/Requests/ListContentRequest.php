<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Query string accepted by the public list endpoints (works, posts, products). */
class ListContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'tag' => ['nullable', 'string', 'max:50'],
            'sort' => ['nullable', Rule::in(['latest', 'oldest', 'price_asc', 'price_desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
