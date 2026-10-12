<?php

namespace App\Http\Requests\Categories;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
        $categoryId = is_object($this->route('category')) ? $this->route('category')->id : $this->route('category') ?? $this->input('id');

        return [
            'name' => 'required|string|max:255|unique:categories,name,'.$categoryId.',id',
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }
    public function messages()
    {
        return [
            'name.string' => 'الاسم يجب ان يكون نص',
            'name.max' => 'الاسم لا يجب ان يتجاوز 255 حرف',
            'name.unique' => 'الاسم موجود بالفعل',
            'image.file' => 'الصورة يجب ان تكون ملف',
            'image.mimes' => 'الصورة يجب ان تكون ملف صالح',
            'image.max' => 'الصورة لا يجب ان تتجاوز 2048 كيلوبايت',
        ];
    }
}
