<?php

namespace App\Http\Requests\Wallets;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawFromWalletRequest extends FormRequest
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
            "amount"=>"required|numeric|min:1",
        ];
    }
    public function messages(): array
    {
        return [
            "amount.required"=>"المبلغ مطلوب",
            "amount.numeric"=>"المبلغ يجب ان يكون رقم",
            "amount.min"=>"المبلغ يجب ان يكون اكبر من 0",
        ];
    }
}
