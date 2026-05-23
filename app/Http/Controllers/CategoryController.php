<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\StoreRequest;
use App\Http\Requests\Categories\UpdateRequest;
use App\Models\Category;
use Illuminate\Http\Request;
class CategoryController extends Controller
{


    public function index(){
        $categories = Category::with('media')->cursorPaginate(30);
        return $this->successResponse(
            data:$categories,
            message:"تم جلب جميع الأقسام بنجاح",
            statusCode:200
        );
    }
    public function categoriesWithProducts(){
        $categories = Category::with('media')->with('products')->cursorPaginate(30);
        return $this->successResponse(
            data:$categories,
            message:"تم جلب جميع الأقسام بنجاح",
            statusCode:200
        );
    }

    public function store(StoreRequest $request){
        $category = $request->validated();
        $category = Category::create([
            "name" => $category['name'],
        ]);
        $category->addMedia($request->image)->toMediaCollection('category_images');
        return $this->successResponse(
            data:[
                "category"=>$category,
                "image"=>$category->getFirstMediaUrl("category_images")
            ],
            message:"تم إنشاء قسم بنجاح",
            statusCode:201
        );
    }

    public function show($id){
        $category = Category::with('products')->find($id);
        $image = $category->getFirstMediaUrl("category_images");
        $data=[
            "category"=>$category,
            "image"=>$image
        ];
        if(!$category){
            return $this->errorResponse(
                message:"القسم غير موجود",
                statusCode:404
            );
        }
        return $this->successResponse(
            data:$data,
            message:"تم جلب القسم بنجاح",
            statusCode:200
        );
    }
    public function update(UpdateRequest $request,$id){
        $category = Category::find($id);
        if(!$category){
            return $this->errorResponse(
                message:"القسم غير موجود",
                statusCode:404
            );
        }
        $category->update(
            $request->only(['name'])
        );
        if($request->hasFile('image')){
            $category->clearMediaCollection('category_images');
            $category->addMedia($request->image)->toMediaCollection('category_images');
        }
        $data = [
            "category"=>$category,
            "image"=>$category->getFirstMediaUrl("category_images")
        ];
        return $this->successResponse(
            data:$data,
            message:"تم تحديث القسم بنجاح",
            statusCode:200
        );
    }
    public function destroy($id){
        $category = Category::find($id);
        if(!$category){
            return $this->errorResponse(
                message:"القسم غير موجود",
                statusCode:404
            );
        }
        if($category->products()->exists()){
            return $this->errorResponse(
                message:"لا يمكن حذف القسم لأنه يحتوي على منتجات",
                statusCode:400
            );
        }
        $category->delete();

        return $this->successResponse(
            data:null,
            message:"تم حذف القسم بنجاح",
            statusCode:200
        );
    }
}
