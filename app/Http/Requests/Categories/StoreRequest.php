<?php

namespace App\Http\Requests\Categories;

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
            'name' => 'required|string|max:255|unique:categories,name',
            'image' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب ان يكون نص',
            'name.max' => 'الاسم لا يجب ان يتجاوز 255 حرف',
            'name.unique' => 'الاسم موجود بالفعل',
            'image.required' => 'الصورة مطلوبة',
            'image.file' => 'الصورة يجب ان تكون ملف',
            'image.mimes' => 'الصورة يجب ان تكون ملف صالح',
            'image.max' => 'الصورة لا يجب ان تتجاوز 2MB',
        ];
    }
}
