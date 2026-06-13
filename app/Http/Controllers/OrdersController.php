<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Target;
use App\Models\Setting;
use App\Models\UserTarget;
use App\Models\MonthlyTarget;
use App\Models\UserMonthlyTarget;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class OrdersController extends Controller
{
    public function createOrder(){
        $user_id = auth()->id();
        $cartItems = Cart::where('user_id', $user_id)->get();
        $settings=Setting::first();
        $wallet=auth()->user()->wallet;
        if (!$wallet) {
            $wallet = auth()->user()->wallet()->create(['balance' => 0]);
        }

        $totalPrice = $cartItems->sum('total_price');

        if($cartItems->isEmpty()){
            return $this->errorResponse(
                'سلة المشتريات فارغة',
                404
            );
        }
        if($totalPrice<$settings->min_order_total_price){
            return $this->errorResponse(
                'سعر الطلب أقل من المطلوب',
                404
            );
        }

        if($cartItems->count()<$settings->min_order_products_count){
            return $this->errorResponse(
                'عدد المنتجات أقل من المطلوب',
                404
            );
        }
        $discount_amount=0;
        if($wallet->balance > 0){
            if ($wallet->balance >= $totalPrice) {
                $discount_amount = $totalPrice;
                $wallet->update([
                    'balance' => $wallet->balance - $totalPrice,
                ]);
                $totalPrice = 0;
            } else {
                $discount_amount = $wallet->balance;
                $totalPrice = $totalPrice - $wallet->balance;
                $wallet->update([
                    'balance' => 0,
                ]);
            }
        }


        try {
            $order = DB::transaction(function () use ($user_id, $cartItems, $totalPrice, $discount_amount) {
                
                $order = Order::create([
                    'user_id' => $user_id,
                    'total_price' => $totalPrice,
                    'discount_amount' => $discount_amount,
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
        $user = auth()->user();
        $query = Order::where('id', $id)->with('products');
        
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        
        $order = $query->first();
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
        $oldStatus = $order->status;
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



        if($order->status=='تم التوصيل' && $oldStatus !== 'تم التوصيل'){
            $targets=Target::orderBy('points','desc')->get();
            foreach($targets as $target){
                if($target->goal <= $order->total_price){
                    $wallet = $order->user->wallet;
                    if (!$wallet) {
                        $wallet = $order->user->wallet()->create(['balance' => 0]);
                    }
                    $wallet->update([
                        'balance' => $wallet->balance + $target->points,
                    ]);
                    UserTarget::create([
                        'user_id'=>$order->user_id,
                        'target_id'=>$target->id,
                    ]);
                    app(NotificationController::class)->sendOrderStatusNotification(new Request([
                        'profile_id'  => $order->user_id,
                        'order_id' => $order->id,
                        'status'   => 'تهانينا! لقد ربحت '.$target->points.' نقطة'
                    ]));
                    break;
                }
            }

            $profile = $order->user->profile;
            if (!$profile) {
                $profile = $order->user->profile()->create([
                    'total_orders_price_in_current_month' => 0
                ]);
            }
            $newTotal = $profile->total_orders_price_in_current_month + $order->total_price;
            $profile->update([
                'total_orders_price_in_current_month' => $newTotal,
            ]);

            $achievedMonthlyTargetIds = UserMonthlyTarget::where('user_id', $order->user_id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->pluck('monthly_target_id')
                ->toArray();

            $unachievedMonthlyTargets = MonthlyTarget::whereNotIn('id', $achievedMonthlyTargetIds)
                ->orderBy('points', 'asc')
                ->get();

            foreach ($unachievedMonthlyTargets as $monthlyTarget) {
                if ($newTotal >= $monthlyTarget->goal) {
                    $wallet = $order->user->wallet;
                    if (!$wallet) {
                        $wallet = $order->user->wallet()->create(['balance' => 0]);
                    }
                    $wallet->update([
                        'balance' => $wallet->balance + $monthlyTarget->points,
                    ]);

                    UserMonthlyTarget::create([
                        'user_id' => $order->user_id,
                        'monthly_target_id' => $monthlyTarget->id,
                    ]);

                    app(NotificationController::class)->sendOrderStatusNotification(new Request([
                        'profile_id' => $order->user_id,
                        'order_id' => $order->id,
                        'status' => 'تهانينا! لقد حققت الهدف الشهري وربحت ' . $monthlyTarget->points . ' نقطة',
                    ]));
                }
            }
        }
    

        if($order->status=='ملغي'){
            if($order->discount_amount>0){
                $wallet=Wallet::where('user_id',$order->user_id)->first();
                if ($wallet) {
                    $wallet->update([
                        'balance'=>($wallet->balance)+($order->discount_amount),
                    ]);
                }
                $order->update([
                    'discount_amount'=>0,
                ]);
            }

            if ($oldStatus == 'تم التوصيل') {
                $targets = Target::orderBy('points', 'desc')->get();
                foreach ($targets as $target) {
                    if ($target->goal <= $order->total_price) {
                        $wallet = $order->user->wallet;
                        if ($wallet) {
                            $wallet->update([
                                'balance' => max(0, $wallet->balance - $target->points),
                            ]);
                        }
                        $userTarget = UserTarget::where('user_id', $order->user_id)
                            ->where('target_id', $target->id)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($userTarget) {
                            $userTarget->delete();
                        }
                        break;
                    }
                }

                $profile = $order->user->profile;
                if ($profile) {
                    $oldTotal = $profile->total_orders_price_in_current_month;
                    $newTotal = max(0, $oldTotal - $order->total_price);
                    $profile->update([
                        'total_orders_price_in_current_month' => $newTotal,
                    ]);
                } else {
                    $newTotal = 0;
                }

                $achievedMonthlyTargets = UserMonthlyTarget::where('user_id', $order->user_id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->get();

                foreach ($achievedMonthlyTargets as $userMonthlyTarget) {
                    $monthlyTarget = $userMonthlyTarget->monthlyTarget;
                    if ($monthlyTarget && $monthlyTarget->goal > $newTotal) {
                        $wallet = $order->user->wallet;
                        if ($wallet) {
                            $wallet->update([
                                'balance' => max(0, $wallet->balance - $monthlyTarget->points),
                            ]);
                        }
                        $userMonthlyTarget->delete();
                    }
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
