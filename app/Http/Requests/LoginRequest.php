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
        // No strength rules here: they belong to register/profile, and older
        // accounts may still have a weaker password that must keep working.
        return [
            'email' => 'required|string|email:rfc,filter|max:100',
            'password' => 'required|string|max:100',
        ];
    }
}
