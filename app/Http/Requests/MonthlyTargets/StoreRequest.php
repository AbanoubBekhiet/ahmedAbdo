<?php

namespace App\Http\Requests\MonthlyTargets;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'goal' => 'required|integer|min:1|unique:monthly_targets,goal',
            'points' => 'required|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'goal.required' => 'الهدف مطلوب.',
            'goal.integer' => 'الهدف يجب أن يكون رقمًا.',
            'goal.min' => 'الهدف يجب أن يكون 1 على الأقل.',
            'goal.unique' => 'الهدف موجود بالفعل.',
            'points.required' => 'النقاط مطلوبة.',
            'points.integer' => 'النقاط يجب أن تكون رقمًا.',
            'points.min' => 'النقاط يجب أن تكون 1 على الأقل.',
        ];
    }
}
