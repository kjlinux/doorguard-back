<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoorEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'door_id' => 'required|exists:doors,id',
            'status' => 'required|string|in:open,closed',
            'card_holder_id' => 'nullable|exists:card_holders,id',
            'timestamp' => 'nullable|date',
        ];
    }
}
