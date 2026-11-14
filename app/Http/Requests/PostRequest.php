<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates both creating and updating a post. */
class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $post = $this->route('post');

        return [
            'image' => [$post ? 'sometimes' : 'required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => ['required', 'string', 'min:3', 'max:255', 'regex:/^[\pL\pN_-]+$/u', Rule::unique('posts')->ignore($post)],
            'description' => ['required', 'string', 'max:50000'],
        ];
    }
}
