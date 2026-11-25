<?php

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // access is already checked by the role:admin middleware
    }

    // Slug made from brand + name, e.g. "nike-air-jordan-1", if it was left empty.
    protected function prepareForValidation(): void
    {
        $brand = Brand::find($this->brand_id)?->name;

        $this->merge([
            'slug' => Str::slug($this->slug ?: trim($brand . ' ' . $this->name)),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('model')?->id;

        return [
            'brand_id' => 'required|integer|exists:brands,id,deleted_at,NULL',
            'name' => ['required', 'string', 'max:100', "regex:/^[\pL\pN\s&'\".,()+\/-]+$/u"],
            'slug' => ['required', 'string', 'max:170', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('models', 'slug')->ignore($id)],
            // Shown in the models section on the homepage. Required for a new model, optional when editing.
            'image' => [$id ? 'nullable' : 'required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'The name contains characters that are not allowed.',
            'slug.required' => 'A slug could not be made from the name, please enter one.',
            'slug.unique' => 'Another model already uses this slug (also check deleted models).',
        ];
    }
}
