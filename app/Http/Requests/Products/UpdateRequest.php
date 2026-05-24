<?php

namespace App\Http\Requests\Products;

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
        $productParam = $this->route('product') ?? $this->route('id');
        $productId = is_object($productParam) ? $productParam->id : $productParam;        
        return [
            'name'=>'required|string|max:255|unique:products,name,' . $productId,
            'category_id'=>'required|exists:categories,id',
            'description'=>'required|string',
            'unit_price'=>'required|numeric',
            'max_quantity'=>'required|integer',
            'unit'=>'required|in:شريط,كرتونة,علبة',
            'status'=>'required|boolean',
            'image'=>'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required'=>'اسم المنتج مطلوب',
            'name.unique'=>'اسم المنتج موجود بالفعل',
            'name.string'=>'اسم المنتج يجب ان يكون نص',
            'name.max'=>'اسم المنتج يجب ان يكون لا يزيد عن 255 حرف',
            'category_id.required'=>'قسم المنتج مطلوب',
            'category_id.exists'=>'قسم المنتج غير موجود',
            'description.required'=>'وصف المنتج مطلوب',
            'description.string'=>'وصف المنتج يجب ان يكون نص',
            'unit_price.required'=>'سعر الوحدة مطلوب',
            'unit_price.numeric'=>'سعر الوحدة يجب ان يكون رقم',
            'max_quantity.required'=>'الكمية القصوى مطلوبة',
            'max_quantity.integer'=>'الكمية القصوى يجب ان تكون رقم صحيح',
            'unit.required'=>'الوحدة مطلوبة',
            'unit.in'=>'الوحدة يجب ان تكون شريط,كرتونة,علبة',
            'status.required'=>'الحالة مطلوبة',
            'status.boolean'=>'الحالة يجب ان تكون boolean',
            'image.image'=>'الصورة يجب ان تكون صورة',
            'image.mimes'=>'الصورة يجب ان تكون من نوع jpeg,png,jpg,gif',
            'image.max'=>'الصورة يجب ان تكون لا تزيد عن 2048 كيلو بايت',
        ];
    }
}
