<?php

namespace App\Http\Requests\Users;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFcmTokenRequest extends FormRequest
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
            'fcm_token' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'fcm_token.required' => 'رمز الجهاز (fcm_token) مطلوب',
            'fcm_token.string' => 'رمز الجهاز يجب أن يكون نصاً',
        ];
    }
}
