<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100', "regex:/^[\pL\s'.-]+$/u"],
            'email' => 'required|string|email:rfc,filter|max:100|unique:users,email',
            // min 8, upper + lower case letter and a number (see AppServiceProvider)
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'password_confirmation' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Ime može da sadrži samo slova, razmake, tačke, apostrofe i crtice.',
            'password_confirmation.required' => 'Ponovite lozinku.',
        ];
    }
}
