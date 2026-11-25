<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_email' => 'required|string|email:rfc,filter|max:100',
            'contact_email' => 'nullable|string|email:rfc,filter|max:100',
            // digits, spaces, +, -, / and brackets, e.g. "+381 11 123 4567"
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\/()-]{6,30}$/'],
            // Only an https link to instagram.com, so the icon can't point to some other site.
            'instagram_url' => ['nullable', 'string', 'max:200', 'url:https', 'regex:/^https:\/\/(www\.)?instagram\.com\/[A-Za-z0-9._\/-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_phone.regex' => 'The phone number may only contain digits, spaces, +, -, / and brackets.',
            'instagram_url.regex' => 'Enter a link to an Instagram profile, e.g. https://www.instagram.com/shopmoda',
            'instagram_url.url' => 'Enter a link to an Instagram profile, e.g. https://www.instagram.com/shopmoda',
        ];
    }
}
