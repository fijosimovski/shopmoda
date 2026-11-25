<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // access is already checked by the role:admin middleware
    }

    // Runs before validation: build the slug from the name if it was left empty.
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->slug ?: $this->name),
        ]);
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id; // null when creating

        return [
            'model_id' => 'required|integer|exists:models,id,deleted_at,NULL', // the brand comes from the model
            'category_id' => 'required|integer|exists:categories,id,deleted_at,NULL',
            'name' => ['required', 'string', 'min:2', 'max:150', "regex:/^[\pL\pN\s&'\".,()+\/-]+$/u"],
            'slug' => ['required', 'string', 'max:170', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')->ignore($productId)],
            'description' => 'nullable|string|max:5000',
            'gender' => ['required', Rule::in(Product::GENDERS)],
            'price' => 'required|numeric|decimal:0,2|min:1|max:9999999',
            'discount_percent' => 'required|integer|min:0|max:90',
            'is_featured' => 'required|boolean',
            'is_active' => 'required|boolean',

            // sizes[0][size] = 42.5, sizes[0][stock] = 10, ...
            'sizes' => 'required|array|min:1|max:40',
            'sizes.*.size' => 'required|numeric|min:15|max:55|multiple_of:0.5|distinct',
            'sizes.*.stock' => 'required|integer|min:0|max:99999',

            'images' => 'nullable|array|max:10',
            'images.*' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:4096', // max 4 MB each
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'The name contains characters that are not allowed.',
            'slug.required' => 'A slug could not be made from the name, please enter one.',
            'sizes.required' => 'Add at least one size.',
            'sizes.*.size.multiple_of' => 'Sizes go in steps of 0.5 (e.g. 42 or 42.5).',
            'sizes.*.size.distinct' => 'The same size is entered twice.',
            'slug.unique' => 'Another product already uses this slug (also check deleted products).',
        ];
    }
}
