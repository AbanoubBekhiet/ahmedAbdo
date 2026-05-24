<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'min_order_products_count' => 'required|integer|min:1',
            'min_order_total_price' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'min_order_products_count.required' => 'يجب إدخال اقل عدد للمنتجات .',
            'min_order_products_count.integer' => 'اقل عدد للمنتجات يجب ان يكون رقم صحيح .',
            'min_order_products_count.min' => 'اقل عدد للمنتجات هو 1 .',
            'min_order_total_price.required' => 'يجب إدخال اقل سعر للمنتجات .',
            'min_order_total_price.integer' => 'اقل سعر للمنتجات يجب ان يكون رقم صحيح .',
            'min_order_total_price.min' => 'اقل سعر للمنتجات هو 1 .',
        ];
    }
}
