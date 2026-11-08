<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['login' => ['required', 'string', 'max:150'], 'password' => ['required', 'string', 'max:255']];
    }
}
