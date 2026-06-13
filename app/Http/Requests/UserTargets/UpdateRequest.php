<?php

namespace App\Http\Requests\UserTargets;

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
            'target_id' => 'sometimes|required|exists:targets,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'المستخدم مطلوب.',
            'user_id.exists' => 'المستخدم غير موجود.',
            'target_id.required' => 'الهدف مطلوب.',
            'target_id.exists' => 'الهدف غير موجود.',
        ];
    }
}
