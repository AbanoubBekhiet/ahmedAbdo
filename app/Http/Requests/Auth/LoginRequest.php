<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'password' => 'required|string',
            'phone_number' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'ادخل كلمة السر',
            'phone_number.required' => 'ادخل رقم الهاتف',
        ];
    }

}
