<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'phone_number' => ['nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]*$/', 'min:10', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:5000'],
        ];
    }
}
