<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:practice,deep,memorize'],
            'size' => ['required', 'in:small,medium,large'],
            'difficulty' => ['required', 'integer', 'between:1,3'],
            'due_date' => ['required', 'date'],
            'cause_id' => ['required', 'integer', 'exists:causes,id'],
        ];
    }
}
