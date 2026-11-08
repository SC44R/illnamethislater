<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DismissRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer'],
            'success' => ['required', 'boolean'],
            'elapsed_ms' => ['required', 'integer', 'min:0', 'max:60000'],
        ];
    }
}
