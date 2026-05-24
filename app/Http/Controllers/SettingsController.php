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
        if(!$setting) {
            $setting = Setting::create([
                'min_order_products_count' => $validated['min_order_products_count'],
                'min_order_total_price' => $validated['min_order_total_price'],
            ]);
        } else {
            $setting->update([
                'min_order_products_count' => $request->min_order_products_count,
                'min_order_total_price' => $request->min_order_total_price,
            ]);
        }

        return $this->successResponse([
            "message" => "تم تحديث الاعدادات بنجاح",
            "data" => $setting,
        ],200);
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
