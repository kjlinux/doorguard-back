<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestMqttRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'broker' => ['required', 'string'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'topic' => ['required', 'string'],
        ];
    }
}
