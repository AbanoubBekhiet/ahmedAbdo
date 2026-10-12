<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Target;
use App\Http\Requests\Targets\StoreRequest;
use App\Http\Requests\Targets\UpdateRequest;

class TargetController extends Controller
{
    public function index()
    {
        $targets = Target::all();
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم جلب الاهداف بنجاح',
            'data' => $targets
        ]);
    }
    public function store(StoreRequest $request)
    {
        $validatedData = $request->validated();
        $target = Target::create($validatedData);
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم انشاء الهدف بنجاح',
            'data' => $target
        ],statusCode:200);
    }
    public function update(UpdateRequest $request,$id)
    {
        $validatedData = $request->validated();
        $target = Target::find($id);
        if (!$target) {
            return $this->errorResponse('الهدف غير موجود', 404);
        }
        $target->update($validatedData);
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم تحديث الهدف بنجاح',
            'data' => $target
        ],statusCode:200);
    }
    public function destroy($id)
    {
        $target = Target::find($id);
        if(!$target){
            return $this->errorResponse('الهدف غير موجود', 404);
        }
        $target->delete();
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم حذف الهدف بنجاح',
            'data' => null
        ],statusCode:200);
    }
    public function show(Target $target)
    {
        if(!$target){
            return $this->errorResponse([
                'status' => 'error',
                'message' => 'الهدف غير موجود',
            ], 404);
        }
        return $this->successResponse([
            'status' => 'success',
            'message' => 'تم جلب الهدف بنجاح',
            'data' => $target
        ],statusCode:200);
    }
}
