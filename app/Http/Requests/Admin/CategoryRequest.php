<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->slug ?: $this->name),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\pL\pN\s&'.-]+$/u"],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('categories', 'slug')->ignore($id)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'The name may only contain letters, numbers, spaces and & . \' -',
            'slug.required' => 'A slug could not be made from the name, please enter one.',
        ];
    }
}
