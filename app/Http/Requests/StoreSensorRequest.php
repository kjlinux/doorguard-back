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
            'mqtt_topic' => ['required', 'string', 'max:255', 'unique:sensors,mqtt_topic'],
        ];
    }

    public function messages(): array
    {
        return [
            'mqtt_topic.unique' => 'Ce topic MQTT est déjà utilisé par un autre capteur.',
        ];
    }
}
