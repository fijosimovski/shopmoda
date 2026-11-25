<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            // the controller also checks that this size exists for the product and is in stock
            'size' => 'required|numeric|min:15|max:55|multiple_of:0.5',
            'quantity' => 'required|integer|min:1|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'size.required' => 'Izaberite veličinu.',
        ];
    }
}
