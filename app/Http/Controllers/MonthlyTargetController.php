<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonthlyTarget;
use App\Http\Requests\MonthlyTargets\StoreRequest;
use App\Http\Requests\MonthlyTargets\UpdateRequest;

class MonthlyTargetController extends Controller
{
    public function index()
    {
        $monthlyTargets = MonthlyTarget::all();
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم جلب الاهداف الشهرية بنجاح',
            'data' => $monthlyTargets
        ]);
    }

    public function store(StoreRequest $request)
    {
        $validatedData = $request->validated();
        $monthlyTarget = MonthlyTarget::create($validatedData);
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم انشاء الهدف الشهري بنجاح',
            'data' => $monthlyTarget
        ], statusCode: 200);
    }

    public function update(UpdateRequest $request, $id)
    {
        $validatedData = $request->validated();
        $monthlyTarget = MonthlyTarget::find($id);
        if (!$monthlyTarget) {
            return $this->errorResponse('الهدف الشهري غير موجود', 404);
        }
        $monthlyTarget->update($validatedData);
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم تحديث الهدف الشهري بنجاح',
            'data' => $monthlyTarget
        ], statusCode: 200);
    }

    public function destroy($id)
    {
        $monthlyTarget = MonthlyTarget::find($id);
        if (!$monthlyTarget) {
            return $this->errorResponse('الهدف الشهري غير موجود', 404);
        }
        $monthlyTarget->delete();
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم حذف الهدف الشهري بنجاح',
            'data' => null
        ], statusCode: 200);
    }

    public function show(MonthlyTarget $monthlyTarget)
    {
        if (!$monthlyTarget) {
            return $this->errorResponse([
                'status' => 'error',
                'message' => 'الهدف الشهري غير موجود',
            ], 404);
        }
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم جلب الهدف الشهري بنجاح',
            'data' => $monthlyTarget
        ], statusCode: 200);
    }
}
