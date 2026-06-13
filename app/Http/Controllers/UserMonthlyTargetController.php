<?php

namespace App\Http\Controllers;

use App\Models\UserMonthlyTarget;
use App\Http\Requests\UserMonthlyTargets\StoreRequest;
use App\Http\Requests\UserMonthlyTargets\UpdateRequest;
use Illuminate\Http\Request;

class UserMonthlyTargetController extends Controller
{
    public function index()
    {
        $userMonthlyTargets = UserMonthlyTarget::with(['user', 'monthlyTarget'])->get();
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم جلب الأهداف الشهرية للمستخدمين بنجاح',
            'data' => $userMonthlyTargets
        ]);
    }

    public function store(StoreRequest $request)
    {
        $validatedData = $request->validated();
        $userMonthlyTarget = UserMonthlyTarget::create($validatedData);

        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم ربط المستخدم بالهدف الشهري بنجاح',
            'data' => $userMonthlyTarget->load(['user', 'monthlyTarget'])
        ], statusCode: 200);
    }

    public function show($id)
    {
        $userMonthlyTarget = UserMonthlyTarget::with(['user', 'monthlyTarget'])->find($id);
        if (!$userMonthlyTarget) {
            return $this->errorResponse('الهدف الشهري للمستخدم غير موجود', 404);
        }

        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم جلب الهدف الشهري للمستخدم بنجاح',
            'data' => $userMonthlyTarget
        ], statusCode: 200);
    }

    public function update(UpdateRequest $request, $id)
    {
        $userMonthlyTarget = UserMonthlyTarget::find($id);
        if (!$userMonthlyTarget) {
            return $this->errorResponse('الهدف الشهري للمستخدم غير موجود', 404);
        }

        $validatedData = $request->validated();
        $userMonthlyTarget->update($validatedData);

        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم تحديث الهدف الشهري للمستخدم بنجاح',
            'data' => $userMonthlyTarget->load(['user', 'monthlyTarget'])
        ], statusCode: 200);
    }

    public function destroy($id)
    {
        $userMonthlyTarget = UserMonthlyTarget::find($id);
        if (!$userMonthlyTarget) {
            return $this->errorResponse('الهدف الشهري للمستخدم غير موجود', 404);
        }

        $userMonthlyTarget->delete();

        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم حذف الهدف الشهري للمستخدم بنجاح',
            'data' => null
        ], statusCode: 200);
    }
}
