<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Http\Requests\Settings\UpdateRequest;

class SettingsController extends Controller
{
    public function updateSettings(UpdateRequest $request)
    {
        $validated = $request->validated();
        $setting = Setting::first();

        $phoneNumber = $request->phone_number ?? $request->support_phone;

        $updateData = [];
        if (!is_null($phoneNumber)) {
            $updateData['phone_number'] = $phoneNumber;
        }
        if (!is_null($request->min_order_products_count)) {
            $updateData['min_order_products_count'] = $request->min_order_products_count;
        }
        if (!is_null($request->min_order_total_price)) {
            $updateData['min_order_total_price'] = $request->min_order_total_price;
        }

        if (!$setting) {
            $setting = Setting::create(array_merge([
                'min_order_products_count' => 1,
                'min_order_total_price' => 0,
                'phone_number' => '01000000000',
            ], $updateData));
        } else {
            if (!empty($updateData)) {
                $setting->update($updateData);
            }
        }

        return $this->successResponse([
            "message" => "تم تحديث الاعدادات بنجاح",
            "data" => $setting,
        ], 200);
    }

    public function getSettings()
    {
        $setting = Setting::first();
        if(!$setting) {
            return $this->errorResponse([
                "message" => "لا يوجد اعدادات",
                "data" => null,
            ],404);
        }
        return $this->successResponse([
            "message" => "تم الحصول على الاعدادات بنجاح",
            "data" => $setting,
        ],200);
    }
}
