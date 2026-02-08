<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uid' => 'required|string|max:50|unique:badges,uid',
            'holder_name' => 'required|string|max:255',
        ];
    }
}
