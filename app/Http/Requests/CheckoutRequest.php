<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100', "regex:/^[\pL\s'.-]+$/u"],
            'email' => 'required|string|email:rfc,filter|max:100',
            // optional leading +, then digits, spaces, slashes or hyphens (e.g. 064 123 4567, +381 64/123-4567)
            'phone' => ['required', 'string', 'min:6', 'max:20', 'regex:/^\+?[0-9][0-9\s\/-]{5,19}$/'],
            'address' => ['required', 'string', 'min:5', 'max:150', "regex:/^[\pL\pN\s.,'\/#-]+$/u"],
            'city' => ['required', 'string', 'min:2', 'max:80', "regex:/^[\pL\s'.-]+$/u"],
            'note' => 'nullable|string|max:500',
            // Checked against the coupons table in CheckoutController (Coupons service).
            'coupon_code' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Ime može da sadrži samo slova, razmake, tačke, apostrofe i crtice.',
            'phone.regex' => 'Unesite ispravan broj telefona (cifre, razmaci, / ili -, opciono + na početku).',
            'address.regex' => 'Adresa sadrži nedozvoljene znakove.',
            'city.regex' => 'Grad može da sadrži samo slova, razmake, tačke, apostrofe i crtice.',
            'coupon_code.regex' => 'Ovaj kupon kod nije ispravan.',
        ];
    }
}
