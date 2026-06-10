<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Target;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class OrdersController extends Controller
{
    public function createOrder(){
        $user_id = auth()->id();
        $cartItems = Cart::where('user_id', $user_id)->get();
        
        if($cartItems->isEmpty()){
            return $this->errorResponse(
                'سلة المشتريات فارغة',
                404
            );
        }

        try {
            $order = DB::transaction(function () use ($user_id, $cartItems) {
                
                $totalPrice = $cartItems->sum('total_price');

                $order = Order::create([
                    'user_id' => $user_id,
                    'total_price' => $totalPrice,
                ]);

                foreach($cartItems as $cartItem){
                    $order->products()->attach($cartItem->product_id, [
                        'number_of_units' => $cartItem->number_of_units,
                        'unit_price' => $cartItem->unit_price,
                        'total_product_price' => $cartItem->total_price,
                    ]);
                }

                Cart::where('user_id', $user_id)->delete();

                return $order;
            });

            return $this->successResponse([
                'status_code' => 201, 
                'message' => 'تم إنشاء الطلب بنجاح',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            Log::error('Order Creation Failed: ' . $e->getMessage());

            return $this->errorResponse(
                'حدث خطأ أثناء إتمام الطلب، يرجى المحاولة لاحقاً',
                500
            );
        }
    }
    public function getMyOrders(Request $request){
        $user_id=auth()->id();
        $direction = in_array(strtolower($request->query('order')), ['asc', 'asce']) ? 'asc' : 'desc';

        $orders=Order::where('user_id',$user_id)->with('products')->orderBy('created_at',$direction)->cursorPaginate(30);
        return $this->successResponse([
            'status_code'=>200, 
            'message'=>'تم جلب الطلبات بنجاح',
            'data'=>$orders
            ]
        );
    }
    public function getAllOrders(Request $request){
        $direction = in_array(strtolower($request->query('order')), ['asc', 'asce']) ? 'asc' : 'desc';

        $orders=Order::with('products')->orderBy('created_at',$direction)->cursorPaginate(30);
        return response()->json([
            'status'=>200,
            'message'=>'تم جلب الطلبات بنجاح',
            'data'=>$orders
        ],200);
    }
    public function getSingleOrder($id){
        $user_id=auth()->id();
        $order=Order::where('id',$id)->where('user_id',$user_id)->with('products')->first();
        if(!$order){
            return $this->errorResponse(
                'الطلب غير موجود',
                404
            );
        }
        return $this->successResponse([
            'status_code'=>200, 
            'message'=>'تم جلب الطلب بنجاح',
            'data'=>$order
            ]
        );
    } 
    public function updateOrderStatus($id,Request $request){
        $valied=['ملغي','تم التوصيل','جاري التجهيز'];
        if(!in_array($request->status,$valied)){
            return $this->errorResponse(
                'الحالة غير صالحة',
                400
            );
        }
        $order=Order::find($id);
        if(!$order){
            return $this->errorResponse(
                'الطلب غير موجود',
                404
            );
        }
        $order->update([
            'status'=>$request->status,
        ]);


        $statusMessages = [
            'ملغي'        => 'عذراً! تم إلغاء طلبك.',
            'تم التوصيل'    => 'تم توصيل طلبك بنجاح ',
            'جاري التجهيز' => 'جاري تجهيز طلبك '
        ];

        $defaultMessage = 'تم تحديث حالة طلبك.';
        $messageText = $statusMessages[$request->status] ?? $defaultMessage;
        app(NotificationController::class)->sendOrderStatusNotification(new Request([
            'profile_id'  => $order->user_id,
            'order_id' => $order->id,
            'status'   => $messageText
        ]));



        if($order->status=='تم التوصيل'){
            $targets=Target::orderBy('points','desc')->get();
            foreach($targets as $target){
                if($target->goal <= $order->total_price){
                $order->user->wallet->update([
                    'balance'=>($order->user->wallet->balance)+($target->points),
                ]);
                app(NotificationController::class)->sendOrderStatusNotification(new Request([
                    'profile_id'  => $order->user_id,
                    'order_id' => $order->id,
                    'status'   => 'تهانينا! لقد ربحت '.$target->points.' نقطة'
                ]));
                break;
            }
        }
    }




        
        return $this->successResponse([
            'status_code'=>200, 
            'message'=>'تم تحديث الطلب بنجاح',
            'data'=>$order
            ]
        );
    }
}
