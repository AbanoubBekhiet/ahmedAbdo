<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Carts\AddToCartRequest;
use App\Http\Requests\Carts\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
class CartController extends Controller
{
    public function myCart()
    {
        $user = Auth::user();
        $cartItems = $user->carts()->with('product.media')->get();
        return $this->successResponse([
            'status' => true,
            'message' => 'تم جلب سلة المشتريات بنجاح',
            'data' => $cartItems,
        ]);
    }

    public function addToCart(AddToCartRequest $request){
        $request->validated();
        $user_id=Auth::id();
        $product_from_products=Product::find($request->product_id);
        if(!$product_from_products){
            return $this->errorResponse(
                'المنتج غير موجود',
                404
            );
        }
        if($product_from_products->max_quantity<$request->number_of_units){
            return $this->errorResponse(
                'لا يمكن اضافه اكثر من '.$product_from_products->max_quantity.' من هذا المنتج',
                400
            );
        }
        $product=Cart::where('product_id', $request->product_id)->where('user_id', $user_id)->first();
        if($product){
            return $this->errorResponse(
                'المنتج موجود بالفعل في سلة المشتريات',
                400
            );
        }
        
        $totalProductPrice=$request->unit_price*$request->number_of_units;
        $cartItem=Cart::create([
            'user_id' => $user_id,
            'product_id' => $request->product_id,
            'number_of_units' => $request->number_of_units,
            'unit_price' => $request->unit_price,
            'total_price' => $totalProductPrice,
        ]);
        return $this->successResponse([
            'status' => true,
            'message' => 'تم إضافة المنتج إلى سلة المشتريات بنجاح',
            'data' => $cartItem,
        ]);
    
    }

    public function updateCartItem(UpdateCartItemRequest $request,$id){
        $request->validated();
        $user_id=Auth::id();
        $product_from_products=Product::find($request->product_id);
        if(!$product_from_products){
            return $this->errorResponse(
                'المنتج غير موجود',
                404
            );
        }
        if($product_from_products->max_quantity<$request->number_of_units){
            return $this->errorResponse(
                'لا يمكن اضافه اكثر من '.$product_from_products->max_quantity.' من هذا المنتج',
                400
            );
        }
        $product=Cart::where('id',$id)->where('user_id',$user_id)->first();
        if(!$product){
            return $this->errorResponse(
                false,
                'المنتج غير موجود في سلة المشتريات',
                404
            );
        }
        $totalProductPrice=$request->unit_price*$request->number_of_units;
        $product->update([
            'number_of_units' => $request->number_of_units,
            'unit_price' => $request->unit_price,
            'total_price' => $totalProductPrice,
        ]);
        return $this->successResponse([
            'status' => true,
            'message' => 'تم تحديث المنتج في سلة المشتريات بنجاح',
            'data' => $product,
        ]);

    }

    public function deleteCartItem($id){
        $user_id=Auth::id();

        $product=Cart::where('id',$id)->where('user_id',$user_id)->first();
        if(!$product){
            return $this->errorResponse(
                'المنتج غير موجود في سلة المشتريات',    
                404
            );
        }
        $product->delete();
        return $this->successResponse([
            'status' => true,
            'message' => 'تم حذف المنتج من سلة المشتريات بنجاح',
        ]);

    }

    public function emptyCart(){
        $user_id=Auth::id();
        
        $cartItems=Cart::where('user_id',$user_id)->get();
        if($cartItems->isEmpty()){
            return $this->errorResponse(
                'سلة المشتريات فارغة',
                404
            );
        }
        $cartItems->each->delete();
        return $this->successResponse([
            'status' => true,
            'message' => 'تم حذف جميع المنتجات من سلة المشتريات بنجاح',
        ]);

    }

    public function usersCart(){
        $users=User::with('carts.product.media')->where('role','customer')->get();
        
        return $this->successResponse([
            'status' => true,
            'message' => 'تم جلب سلة المشتريات بنجاح',
            'data' => $users,
        ]);
    }
}
