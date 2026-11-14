<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates both creating and updating a work. Who may call it is decided by the `can:manage-content` route middleware. */
class WorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $work = $this->route('work');

        return [
            'image' => [$work ? 'sometimes' : 'required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            'full_image' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => ['required', 'string', 'min:3', 'max:255', 'regex:/^[\pL\pN_-]+$/u', Rule::unique('works')->ignore($work)],
            'timeline' => ['required', 'string', 'max:255'],
            'publish_date' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'tags' => ['required', 'string', 'max:255'],
            'project_link' => ['nullable', 'url:http,https', 'max:2048'],
            'overview' => ['nullable', 'string', 'max:5000'],
            'learn' => ['nullable', 'string', 'max:5000'],
            'description' => ['required', 'string', 'max:20000'],
        ];
    }
}
