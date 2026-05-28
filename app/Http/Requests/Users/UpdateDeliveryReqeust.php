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
        $userParam = $this->route('delivery_boy') ?? $this->route('id');
        $userId = is_object($userParam) ? $userParam->id : $userParam;  

        return [
            'name'=>'required|string|max:255',
            'phone_number'=>'required|string|max:255|unique:users,phone_number,'.$userId,
            'password'=>'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'=>'الاسم مطلوب',
            'phone_number.required'=>'رقم الهاتف مطلوب',
            'phone_number.unique'=>'رقم الهاتف موجود بالفعل',
        ];
    }

}
