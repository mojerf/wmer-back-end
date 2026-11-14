<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates both creating and updating a product. */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'image' => [$product ? 'sometimes' : 'required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => ['required', 'string', 'min:3', 'max:255', 'regex:/^[\pL\pN_-]+$/u', Rule::unique('products')->ignore($product)],
            'price' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'price_with_discount' => ['nullable', 'integer', 'min:0', 'lt:price'],
            'expert' => ['required', 'string', 'max:1000'],
            'description' => ['required', 'string', 'max:20000'],
            'download_link' => ['nullable', 'url:https', 'max:2048'],
        ];
    }
}
