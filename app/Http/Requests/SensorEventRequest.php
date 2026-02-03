<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SensorEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sensor_id' => 'required|exists:sensors,id',
            'card_id' => 'nullable|string',
            'action' => 'required|string|in:open,closed',
            'timestamp' => 'nullable|date',
        ];
    }
}
