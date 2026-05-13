<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['webapp', 'mobile', 'api', 'data', 'custom'])],
            'template_id' => ['nullable', 'exists:templates,id'],
            'mode' => ['nullable', Rule::in(['template', 'conversational'])],
        ];
    }
}
