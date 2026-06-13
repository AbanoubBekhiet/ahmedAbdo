<?php

namespace App\Http\Controllers;

use App\Models\UserTarget;
use App\Http\Requests\UserTargets\StoreRequest;
use App\Http\Requests\UserTargets\UpdateRequest;
use Illuminate\Http\Request;

class UserTargetController extends Controller
{
    public function index()
    {
        $userTargets = UserTarget::with(['user', 'target'])->get();
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم جلب أهداف المستخدمين بنجاح',
            'data' => $userTargets
        ]);
    }

    public function store(StoreRequest $request)
    {
        $validatedData = $request->validated();
        $userTarget = UserTarget::create($validatedData);
        
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم ربط المستخدم بالهدف بنجاح',
            'data' => $userTarget->load(['user', 'target'])
        ], statusCode: 200);
    }

    public function show($id)
    {
        $userTarget = UserTarget::with(['user', 'target'])->find($id);
        if (!$userTarget) {
            return $this->errorResponse('هدف المستخدم غير موجود', 404);
        }
        
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم جلب هدف المستخدم بنجاح',
            'data' => $userTarget
        ], statusCode: 200);
    }

    public function update(UpdateRequest $request, $id)
    {
        $userTarget = UserTarget::find($id);
        if (!$userTarget) {
            return $this->errorResponse('هدف المستخدم غير موجود', 404);
        }

        $validatedData = $request->validated();
        $userTarget->update($validatedData);

        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم تحديث هدف المستخدم بنجاح',
            'data' => $userTarget->load(['user', 'target'])
        ], statusCode: 200);
    }

    public function destroy($id)
    {
        $userTarget = UserTarget::find($id);
        if (!$userTarget) {
            return $this->errorResponse('هدف المستخدم غير موجود', 404);
        }

        $userTarget->delete();

        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم حذف هدف المستخدم بنجاح',
            'data' => null
        ], statusCode: 200);
    }
}
