<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSensorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'door_id' => ['required', 'exists:doors,id'],
            'mqtt_broker' => ['nullable', 'string', 'max:255'],
            'mqtt_port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'mqtt_topic' => ['required', 'string', 'max:255'],
        ];
    }
}
