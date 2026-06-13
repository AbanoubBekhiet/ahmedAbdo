<?php

namespace App\Http\Requests\UserMonthlyTargets;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|required|exists:users,id',
            'monthly_target_id' => 'sometimes|required|exists:monthly_targets,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'المستخدم مطلوب.',
            'user_id.exists' => 'المستخدم غير موجود.',
            'monthly_target_id.required' => 'الهدف الشهري مطلوب.',
            'monthly_target_id.exists' => 'الهدف الشهري غير موجود.',
        ];
    }
}
