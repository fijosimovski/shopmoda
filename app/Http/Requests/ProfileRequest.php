<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100', "regex:/^[\pL\s'.-]+$/u"],
            // No "email" rule: the email can't be changed from the profile.
            // Changing the password is optional; if a new one is given, the current one is required.
            'password' => ['nullable', 'string', 'confirmed', 'different:current_password', Password::defaults()],
            'password_confirmation' => 'required_with:password|nullable|string',
            'current_password' => 'required_with:password|nullable|string|current_password',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Ime može da sadrži samo slova, razmake, tačke, apostrofe i crtice.',
            'password.different' => 'Nova lozinka mora biti drugačija od trenutne.',
            'password_confirmation.required_with' => 'Ponovite novu lozinku.',
            'current_password.current_password' => 'Trenutna lozinka nije tačna.',
            'current_password.required_with' => 'Unesite trenutnu lozinku da biste postavili novu.',
        ];
    }
}
