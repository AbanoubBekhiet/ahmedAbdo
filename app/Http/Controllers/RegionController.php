<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegionController extends Controller
{
    /**
     * Display a listing of regions.
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();
        
        // Admins and sub-admins see all regions; customers/guests see only active regions
        if ($user && in_array($user->role, ['admin', 'sub_admin'])) {
            $regions = Region::orderBy('name', 'asc')->get();
        } else {
            $regions = Region::where('is_active', true)->orderBy('name', 'asc')->get();
        }

        return $this->successResponse([
            'status_code' => 200,
            'message' => 'تم جلب المناطق بنجاح',
            'data' => $regions
        ]);
    }

    /**
     * Store a newly created region.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:regions,name',
            'min_order_price' => 'nullable|numeric|min:0',
            'min_order_products' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'اسم المنطقة مطلوب.',
            'name.unique' => 'اسم المنطقة مسجل بالفعل.',
            'min_order_price.numeric' => 'الحد الأدنى للسعر يجب أن يكون رقماً.',
            'min_order_price.min' => 'الحد الأدنى للسعر لا يمكن أن يكون سالباً.',
            'min_order_products.integer' => 'الحد الأدنى لعدد المنتجات يجب أن يكون رقماً صحيحاً.',
            'min_order_products.min' => 'الحد الأدنى لعدد المنتجات لا يمكن أن يكون سالباً.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $region = Region::create([
            'name' => $request->name,
            'min_order_price' => $request->min_order_price ?? 0,
            'min_order_products' => $request->min_order_products ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->successResponse([
            'status_code' => 201,
            'message' => 'تمت إضافة المنطقة بنجاح',
            'data' => $region
        ]);
    }

    /**
     * Update the specified region.
     */
    public function update(Request $request, $id)
    {
        $region = Region::find($id);
        if (!$region) {
            return $this->errorResponse('المنطقة غير موجودة.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255|unique:regions,name,' . $id,
            'min_order_price' => 'nullable|numeric|min:0',
            'min_order_products' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ], [
            'name.unique' => 'اسم المنطقة مسجل بالفعل.',
            'min_order_price.numeric' => 'الحد الأدنى للسعر يجب أن يكون رقماً.',
            'min_order_price.min' => 'الحد الأدنى للسعر لا يمكن أن يكون سالباً.',
            'min_order_products.integer' => 'الحد الأدنى لعدد المنتجات يجب أن يكون رقماً صحيحاً.',
            'min_order_products.min' => 'الحد الأدنى لعدد المنتجات لا يمكن أن يكون سالباً.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $region->update($request->only(['name', 'min_order_price', 'min_order_products', 'is_active']));

        return $this->successResponse([
            'status_code' => 200,
            'message' => 'تم تحديث المنطقة بنجاح',
            'data' => $region
        ]);
    }

    /**
     * Remove the specified region.
     */
    public function destroy($id)
    {
        $region = Region::find($id);
        if (!$region) {
            return $this->errorResponse('المنطقة غير موجودة.', 404);
        }

        // Safety Check: Check if region is connected with customers
        $hasCustomers = Profile::where('region_id', $id)->exists();
        if ($hasCustomers) {
            return $this->errorResponse('لا يمكن حذف هذه المنطقة لأنها مرتبطة بعملاء مسجلين بالفعل.', 400);
        }

        $region->delete();

        return $this->successResponse([
            'status_code' => 200,
            'message' => 'تم حذف المنطقة بنجاح'
        ]);
    }
}
