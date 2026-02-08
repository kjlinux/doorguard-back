<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uid' => 'sometimes|string|max:50|unique:badges,uid,' . $this->badge->id,
            'holder_name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
