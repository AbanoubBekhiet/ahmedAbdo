<?php

namespace App\Http\Requests\Users;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileReqeust extends FormRequest
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
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'shop_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'name' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'خط العرض مطلوب',
            'longitude.required' => 'خط الطول مطلوب',
            'shop_name.required' => 'اسم المحل مطلوب',
            'address.required' => 'العنوان مطلوب',
            'name.required' => 'الاسم مطلوب',
            'latitude.numeric' => 'خط العرض يجب ان يكون رقم',
            'longitude.numeric' => 'خط الطول يجب ان يكون رقم',
            'shop_name.string' => 'اسم المحل يجب ان يكون نص',
            'address.string' => 'العنوان يجب ان يكون نص',
            'latitude.between' => 'خط العرض يجب ان يكون بين -90 و 90',
            'longitude.between' => 'خط الطول يجب ان يكون بين -180 و 180',
            'shop_name.max' => 'اسم المحل يجب ان لا يتجاوز 255 حرف',
            'address.max' => 'العنوان يجب ان لا يتجاوز 255 حرف',
            'name.max' => 'الاسم يجب ان لا يتجاوز 255 حرف',
            'name.string' => 'الاسم يجب ان يكون نص',
        ];
    }
}
