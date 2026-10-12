<?php

namespace App\Http\Requests\Carts;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'number_of_units' => 'required|integer|min:1',
            'unit_price' => 'required|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'يرجى اختيار المنتج',
            'product_id.exists' => 'المنتج غير موجود',
            'number_of_units.required' => 'يرجى إدخال عدد الوحدات',
            'number_of_units.integer' => 'عدد الوحدات يجب أن يكون عدد صحيح',
            'number_of_units.min' => 'عدد الوحدات يجب أن يكون 1 على الأقل',
            'unit_price.required' => 'يرجى إدخال سعر الوحدة',
            'unit_price.numeric' => 'سعر الوحدة يجب أن يكون رقم',
        ];
    }
}
