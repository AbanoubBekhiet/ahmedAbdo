<?php

namespace App\Http\Requests\Users;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryReqeust extends FormRequest
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
            'password'=>'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required'=>'كلمة المرور مطلوبة',
            'password.min'=>'كلمة المرور يجب أن تكون 6 حرف كحد أدنى',
        ];
    }

}
