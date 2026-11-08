<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'block_index' => ['required', 'integer', 'between:1,4'],
            'kind' => ['required', 'in:task,free'],
            'task_id' => ['nullable', 'integer'],
        ];
    }
}
