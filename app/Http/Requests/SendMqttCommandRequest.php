<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMqttCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sensor_id' => 'required|exists:sensors,id',
            'command' => 'required|string|in:FORCED_OPEN,REBOOT,RESET,SLEEP,WAKE_UP,STATUS',
        ];
    }
}
