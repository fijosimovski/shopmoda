<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(Announcement::TYPES))],
            'title' => 'required|string|min:3|max:80',
            'body' => 'nullable|string|max:160',
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => 'boolean',
            // "product:12", "model:3", "promotion:5" or empty (checked in after()).
            'link' => ['nullable', 'string', 'max:40', 'regex:/^(' . implode('|', array_keys(Announcement::LINKABLES)) . '):\d+$/'],
            'show_on' => ['required', Rule::in(array_keys(Announcement::SHOW_ON))],
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
        ];
    }

    // The linked product / model / promotion must exist.
    public function after(): array
    {
        return [
            function ($validator) {
                if (!$this->filled('link') || $validator->errors()->has('link')) {
                    return;
                }

                [$type, $id] = explode(':', $this->link, 2);
                $class = Announcement::LINKABLES[$type];

                if (!$class::whereKey((int) $id)->exists()) {
                    $validator->errors()->add('link', 'The linked ' . $type . ' does not exist anymore.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'link.regex' => 'Pick a product, model or promotion from the list.',
            'ends_at.after' => 'The end must be after the start.',
        ];
    }
}
