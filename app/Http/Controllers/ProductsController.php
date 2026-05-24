<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Products\StoreRequest;
use App\Http\Requests\Products\UpdateRequest;
use App\Models\Product;
class ProductsController extends Controller
{
    public function index(Request $request){
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $status = $request->input('status');

        if ($request->filled('search')) {
            $searchQuery = Product::search($search);

            if ($request->filled('category_id')) {
                $searchQuery->where('category_id', (int) $categoryId);
            }

            if ($request->has('status') && $request->input('status') !== null && $request->input('status') !== '') {
                $searchQuery->where('status', (int) $status);
            }

            $keys = $searchQuery->keys();

            $products = Product::with('media')
                ->whereIn('id', $keys)
                ->cursorPaginate(30);
        } else {
            $query = Product::query()->with('media');

            if ($request->filled('category_id')) {
                $query->where('category_id', $categoryId);
            }

            if ($request->has('status') && $request->input('status') !== null && $request->input('status') !== '') {
                $query->where('status', $status);
            }

            $products = $query->cursorPaginate(30);
        }

        return $this->successResponse(
            data:$products,
            message:"تم جلب المنتجات بنجاح",
            statusCode:200
        );
    }
    public function store(StoreRequest $request){
        $validatedData = $request->validated();
        $product = Product::create($validatedData);
        if($request->hasFile('image')){
            $product->addMedia($request->image)->toMediaCollection('product_images');
        }
        $data = [
            "product"=>$product,
            "image"=>$product->getFirstMediaUrl('product_images')
        ];
        return $this->successResponse(
            data:$data,
            message:"تم اضافة المنتج بنجاح",
            statusCode:200
        );
    }
    public function update(UpdateRequest $request,$id){
        $validatedData = $request->validated();
        $product = Product::find($id);
        if(!$product){
            return $this->errorResponse(
                message:"المنتج غير موجود",
                statusCode:404
            );
        }
        $product->update($validatedData);
        if($request->hasFile('image')){
            $product->clearMediaCollection('product_images');
            $product->addMedia($request->image)->toMediaCollection('product_images');
        }
        $data = [
            "product"=>$product,
            "image"=>$product->getFirstMediaUrl('product_images')
        ];
        return $this->successResponse(
            data:$data,
            message:"تم تحديث المنتج بنجاح",
            statusCode:200
        );
    }
    public function destroy($id){
        $product = Product::find($id);
        if(!$product){
            return $this->errorResponse(
                message:"المنتج غير موجود",
                statusCode:404
            );
        }
        $product->delete();
        return $this->successResponse(
            data:null,
            message:"تم حذف المنتج بنجاح",
            statusCode:200
        );
    }
    public function show($id){
        $product = Product::with('media')->find($id);
        if(!$product){
            return $this->errorResponse(
                message:"المنتج غير موجود",
                statusCode:404
            );
        }
        return $this->successResponse(
            data:$product,
            message:"تم جلب المنتج بنجاح",
            statusCode:200
        );
    }

}
