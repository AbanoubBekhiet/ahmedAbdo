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
            'phone_number' => 'nullable|string',
            'support_phone' => 'nullable|string',
            'min_order_products_count' => 'nullable|integer',
            'min_order_total_price' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.string' => 'رقم الهاتف يجب أن يكون نصاً.',
        ];
    }
}
