<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $single = $this->boolean('single_use');

        $this->merge([
            // Empty code = a random one is generated.
            'code' => Str::upper(trim((string) $this->code)) ?: Coupon::generateCode(),
            'applies_to_all' => $this->boolean('applies_to_all'),
            'first_order_only' => $this->boolean('first_order_only'),
            'members_only' => $this->boolean('members_only'),
            'exclude_sale_items' => $this->boolean('exclude_sale_items'),
            'is_active' => $this->boolean('is_active'),
            // Single use = one order in total.
            'usage_limit' => $single ? 1 : $this->usage_limit,
            'usage_limit_per_customer' => $single ? 1 : $this->usage_limit_per_customer,
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('coupon')?->id;
        $targets = ['nullable', 'array'];

        return [
            // withoutTrashed is not used on purpose: a deleted coupon's code can't be reused (old orders show it).
            'code' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Z0-9-]+$/', Rule::unique('coupons', 'code')->ignore($id)],
            'description' => 'nullable|string|max:255',
            'type' => ['required', Rule::in(array_keys(Coupon::TYPES))],
            'value' => ['required', 'numeric', 'min:1', Rule::when($this->type === Coupon::TYPE_PERCENT, 'max:90', 'max:1000000')],
            'min_order_total' => 'nullable|numeric|min:1|max:10000000',
            'max_discount' => 'nullable|numeric|min:1|max:1000000',

            'usage_limit' => 'nullable|integer|min:1|max:1000000',
            'usage_limit_per_customer' => 'nullable|integer|min:1|max:1000',
            'first_order_only' => 'boolean',
            'members_only' => 'boolean',
            'exclude_sale_items' => 'boolean',

            'applies_to_all' => 'boolean',
            'brands' => $targets,
            'brands.*' => 'integer|exists:brands,id',
            'categories' => $targets,
            'categories.*' => 'integer|exists:categories,id',
            'models' => $targets,
            'models.*' => 'integer|exists:models,id',
            'products' => $targets,
            'products.*' => 'integer|exists:products,id',

            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ];
    }

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
            'code.regex' => 'The code may only contain letters, numbers and hyphens.',
            'code.unique' => 'This code is already used by another coupon (also deleted ones).',
        ];
    }
}
