<?php

namespace App\Http\Requests\Offers;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Offer;

class StoreOfferRequest extends FormRequest
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
            'title' => 'required|string',
            'description' => 'required|string',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after:now',
            'price_after_discount' => 'required|numeric',
            'product_id' => 'required|exists:products,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $productId = $this->input('product_id');
            $exists = Offer::where('product_id', $productId)->where('end_date', '>=', date('Y-m-d H:i:s'))->exists();
            
            if ($exists) {
                $validator->errors()->add('product_id', 'هذا المنتج موجود بالفعل في عرض');
            }
        });
    }


    public function messages()
    {
        return [
            'title.required' => 'عنوان العرض مطلوب',
            'title.string' => 'عنوان العرض يجب أن يكون نصًا',
            'description.required' => 'وصف العرض مطلوب',
            'description.string' => 'وصف العرض يجب أن يكون نصًا',
            'end_date.required' => 'تاريخ انتهاء العرض مطلوب',
            'end_date.date_format' => 'تاريخ انتهاء العرض يجب أن يكون بصيغة Y-m-d H:i:s',
            'end_date.after' => 'تاريخ انتهاء العرض يجب أن يكون بعد الوقت الحالي',
            'price_after_discount.required' => 'السعر بعد الخصم مطلوب',
            'price_after_discount.numeric' => 'السعر بعد الخصم يجب أن يكون رقمًا',
            'product_id.required' => 'المنتج مطلوب',
            'product_id.exists' => 'المنتج غير موجود',
        ];
    }
}
