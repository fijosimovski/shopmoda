<?php

namespace App\Http\Requests\Admin;

use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->slug ?: $this->name),
            'applies_to_all' => $this->boolean('applies_to_all'),
            'is_stackable' => $this->boolean('is_stackable'),
            'members_only' => $this->boolean('members_only'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('promotion')?->id;
        $targets = ['nullable', 'array'];

        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('promotions', 'slug')->ignore($id)],
            'description' => 'nullable|string|max:500',
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            'type' => ['required', Rule::in(array_keys(Promotion::TYPES))],
            // percent types: 1-90 %, amounts: any positive number
            'value' => ['required', 'numeric', 'min:1', Rule::when(in_array($this->type, [Promotion::TYPE_PERCENT, Promotion::TYPE_NTH_ITEM]), 'max:90', 'max:1000000')],
            'nth' => [Rule::requiredIf($this->type === Promotion::TYPE_NTH_ITEM), 'nullable', 'integer', 'min:2', 'max:10'],
            'min_cart_total' => [Rule::requiredIf($this->type === Promotion::TYPE_CART_THRESHOLD), 'nullable', 'numeric', 'min:1', 'max:10000000'],

            'applies_to_all' => 'boolean',
            'brands' => $targets,
            'brands.*' => 'integer|exists:brands,id',
            'categories' => $targets,
            'categories.*' => 'integer|exists:categories,id',
            'models' => $targets,
            'models.*' => 'integer|exists:models,id',
            'products' => $targets,
            'products.*' => 'integer|exists:products,id',

            'priority' => 'required|integer|min:0|max:1000',
            'is_stackable' => 'boolean',
            'members_only' => 'boolean',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
        ];
    }

    // Without "whole shop" at least one brand / category / model / product must be picked.
    public function after(): array
    {
        return [
            function ($validator) {
                $picked = collect(['brands', 'categories', 'models', 'products'])->sum(fn ($key) => count($this->input($key, [])));

                if (!$this->boolean('applies_to_all') && $picked === 0) {
                    $validator->errors()->add('applies_to_all', 'Pick at least one brand, category, model or product, or choose "Whole shop".');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'A slug could not be made from the name, please enter one.',
            'nth.required' => 'Enter which pair gets the discount (2 = every 2nd pair).',
            'min_cart_total.required' => 'Enter how much the customer has to spend.',
        ];
    }
}
