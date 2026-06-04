<?php

namespace App\Http\Requests\Users;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryReqeust extends FormRequest
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
            'name'=>'required|string|max:255',
    'phone_number' => [
        'required',
        'string',
        'max:20',
        'unique:users,phone_number',
        'regex:/^(010|011|012|015)[0-9]{8}$/'
    ],
    'password' => 'required|string|max:255',
];
    }

    public function messages(): array
    {
        return [
            'name.required'=>'اسم المستخدم مطلوب',
            'phone.required'=>'رقم الهاتف مطلوب',
            'phone.unique'=>'رقم الهاتف موجود بالفعل',
            'password.required'=>'كلمة المرور مطلوبة',
        ];
    }
}
