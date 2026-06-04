<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SignUpRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'phone_number' => [
                'required',
                'string',
                'max:20',
                'unique:users,phone_number',
                'regex:/^(010|011|012|015)[0-9]{8}$/'],
            'password' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'shop_name' => 'required|string|max:255',
            'address' => 'required|string|max:255'
         ];
    }


        public function messages(): array
    {
        return [
            'name.required' => 'يرجى إدخال الاسم.',
            'phone_number.required' => 'يرجى إدخال رقم الهاتف.',
            'phone_number.regex' => 'يرجى إدخال رقم هاتف صحيح يبدأ بـ 010 أو 011 أو 012 أو 015.',
            'phone_number.unique' => 'رقم الهاتف مسجل بالفعل.',
            'password.required' => 'يرجى إدخال كلمة المرور.',
            'latitude.required' => 'يرجى إدخال خط العرض.',
            'latitude.numeric' => 'خط العرض يجب أن يكون رقمًا.',
            'longitude.required' => 'يرجى إدخال خط الطول.',
            'longitude.numeric' => 'خط الطول يجب أن يكون رقمًا.',
            'shop_name.required' => 'يرجى إدخال اسم المحل.',
            'address.required' => 'يرجى إدخال العنوان.',
        ];
    }
}
