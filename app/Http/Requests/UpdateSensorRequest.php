<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSensorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'location' => ['sometimes', 'string', 'max:255'],
            'door_id' => ['sometimes', 'exists:doors,id'],
            'mqtt_broker' => ['nullable', 'string', 'max:255'],
            'mqtt_port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'mqtt_topic' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:online,offline'],
        ];
    }
}
