<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Target;
use App\Models\Setting;
use App\Models\UserTarget;
use App\Models\MonthlyTarget;
use App\Models\UserMonthlyTarget;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrdersController extends Controller
{
    public function createOrder(Request $request)
    {
        $user_id     = auth()->id();
        $admin       = User::where('role', 'admin')->first();
        $adminUserId = $admin->id ?? null;
        $cartItems      = Cart::where('user_id', $user_id)->get();
        $settings       = Setting::first();
        $wallet         = auth()->user()->wallet;

        if (!$wallet) {
            $wallet = auth()->user()->wallet()->create(['balance' => 0]);
        }

        $totalPrice = $cartItems->sum('total_price');

        if ($cartItems->isEmpty()) {
            return $this->errorResponse('سلة المشتريات فارغة', 404);
        }

        $user = auth()->user();
        $user->load('profile.region');
        $region = $user->profile->region ?? null;

        $minOrderTotalPrice = ($region && $region->min_order_price !== null) 
            ? (float) $region->min_order_price 
            : 0.0;

        $minOrderProductsCount = ($region && $region->min_order_products !== null) 
            ? (int) $region->min_order_products 
            : 0;

        if ($totalPrice < $minOrderTotalPrice) {
            return $this->errorResponse('سعر الطلب أقل من الحد الأدنى للمنطقة (' . $minOrderTotalPrice . ' ج.م)', 400);
        }

        if ($cartItems->count() < $minOrderProductsCount) {
            return $this->errorResponse('عدد المنتجات في السلة أقل من الحد الأدنى للمنطقة (' . $minOrderProductsCount . ' منتج)', 400);
        }

        $discount_amount = 0;
        $useWallet = $request->boolean('use_wallet') || $request->boolean('use_balance');

        if ($useWallet && $wallet->balance > 0) {
            if ($wallet->balance > $totalPrice) {
                $discount_amount = $totalPrice;
                $wallet->update(['balance' => $wallet->balance - $totalPrice]);
                $totalPrice      = 0;
            } else {
                $discount_amount = $wallet->balance;
                $totalPrice      = $totalPrice - $wallet->balance;
                $wallet->update(['balance' => 0]);
            }
        }

        try {
            $order = DB::transaction(function () use ($user_id, $cartItems, $totalPrice, $discount_amount) {
                $order = Order::create([
                    'user_id'         => $user_id,
                    'total_price'     => $totalPrice,
                    'discount_amount' => $discount_amount,
                ]);

                foreach ($cartItems as $cartItem) {
                    $order->products()->attach($cartItem->product_id, [
                        'number_of_units'     => $cartItem->number_of_units,
                        'unit_price'          => $cartItem->unit_price,
                        'total_product_price' => $cartItem->total_price,
                    ]);
                }

                Cart::where('user_id', $user_id)->delete();

                return $order;
            });

            // Notify admin and sub_admins about new order
            $adminsAndSubAdmins = User::whereIn('role', ['admin', 'sub_admin'])->get();
            $notificationMsg = 'طلب جديد من ' . auth()->user()->name;
            if ($discount_amount > 0) {
                $notificationMsg .= ' (خصم من المحفظة: ' . $discount_amount . ' ج.م)';
            }

            foreach ($adminsAndSubAdmins as $receiver) {
                try {
                    app(NotificationController::class)->sendOrderStatusNotification(new Request([
                        'profile_id' => $receiver->id,
                        'order_id'   => $order->id,
                        'title'      => '🛒 طلب جديد',
                        'status'     => $notificationMsg,
                        'type'       => 'new_order',
                    ]));
                } catch (\Exception $e) {
                    Log::error("Failed to notify user id={$receiver->id}: " . $e->getMessage());
                }
            }

            return $this->successResponse([
                'status_code' => 201,
                'message'     => 'تم إنشاء الطلب بنجاح',
                'data'        => $order,
            ]);
        } catch (\Exception $e) {
            Log::error('Order Creation Failed: ' . $e->getMessage());

            return $this->errorResponse(
                'حدث خطأ أثناء إتمام الطلب، يرجى المحاولة لاحقاً',
                500
            );
        }
    }

    public function getMyOrders(Request $request)
    {
        $user_id   = auth()->id();
        $direction = in_array(strtolower($request->query('order')), ['asc', 'asce']) ? 'asc' : 'desc';
        $orders    = Order::where('user_id', $user_id)->with('products')->orderBy('created_at', $direction)->cursorPaginate(30);

        return $this->successResponse([
            'status_code' => 200,
            'message'     => 'تم جلب الطلبات بنجاح',
            'data'        => $orders,
        ]);
    }

    public function getAllOrders(Request $request)
    {
        $direction = in_array(strtolower($request->query('order')), ['asc', 'asce']) ? 'asc' : 'desc';
        $query = Order::with(['products', 'user.profile']);

        // Date range filtering
        $filter   = $request->query('filter') ?? $request->query('period');
        $startStr = $request->query('start_date') ?? $request->query('period_start');
        $endStr   = $request->query('end_date')   ?? $request->query('period_end');

        if ($startStr && $endStr) {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($startStr)->startOfDay(),
                \Carbon\Carbon::parse($endStr)->endOfDay(),
            ]);
        } elseif ($filter) {
            $now = \Carbon\Carbon::now();
            if ($filter === 'last_month') {
                $query->whereBetween('created_at', [
                    $now->copy()->subMonthNoOverflow()->startOfMonth(),
                    $now->copy()->subMonthNoOverflow()->endOfMonth(),
                ]);
            } elseif ($filter === 'this_year') {
                $query->whereBetween('created_at', [
                    $now->copy()->startOfYear(),
                    $now->copy()->endOfYear(),
                ]);
            } elseif ($filter === 'this_month') {
                $query->whereBetween('created_at', [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                ]);
            }
        }

        // Status filtering
        if ($request->has('status') && !empty($request->query('status'))) {
            $query->where('status', $request->query('status'));
        }

        $orders = $query->orderBy('created_at', $direction)->cursorPaginate(30);

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب الطلبات بنجاح',
            'data'    => $orders,
        ], 200);
    }

    public function getSingleOrder($id)
    {
        $user  = auth()->user();
        $query = Order::where('id', $id)->with(['products', 'user.profile']);

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $order = $query->first();
        if (!$order) {
            return $this->errorResponse('الطلب غير موجود', 404);
        }

        return $this->successResponse([
            'status_code' => 200,
            'message'     => 'تم جلب الطلب بنجاح',
            'data'        => $order,
        ]);
    }

    public function updateOrderStatus($id, Request $request)
    {
        $valid = ['قيد الانتظار', 'تم التاكيد', 'تم الشحن', 'تم التوصيل', 'ملغي'];
        if (!in_array($request->status, $valid)) {
            return $this->errorResponse('الحالة غير صالحة', 400);
        }

        $order = Order::find($id);
        if (!$order) {
            return $this->errorResponse('الطلب غير موجود', 404);
        }

        $oldStatus = $order->status;
        $newStatus = $request->status;

        if ($oldStatus === 'تم التوصيل') {
            return $this->errorResponse('لا يمكن تغيير حالة طلب تم توصيله بالفعل', 400);
        }

        if ($oldStatus === 'ملغي' || $oldStatus === 'ملغاة') {
            return $this->errorResponse('لا يمكن تغيير حالة طلب ملغي بالفعل', 400);
        }

        $ranks = [
            'قيد الانتظار' => 1,
            'تم التاكيد'   => 2,
            'تم الشحن'     => 3,
            'تم التوصيل'   => 4,
            'ملغي'         => 99,
            'ملغاة'        => 99,
        ];

        if ($newStatus !== 'ملغي' && $newStatus !== 'ملغاة') {
            $oldRank = $ranks[$oldStatus] ?? 1;
            $newRank = $ranks[$newStatus] ?? 1;
            if ($newRank <= $oldRank) {
                return $this->errorResponse('لا يمكن إرجاع الطلب إلى حالة سابقة', 400);
            }
        }

        $order->update(['status' => $newStatus]);

        // ── Notify the customer of the status update ───────────────────────
        $statusMessages = [
            'قيد الانتظار' => 'طلبك قيد الانتظار حتى يتم مراجعته.',
            'تم التاكيد'   => 'تم تأكيد طلبك بنجاح ✅',
            'تم الشحن'     => 'تم شحن طلبك وهو في الطريق إليك 🚚',
            'تم التوصيل'   => 'تم توصيل طلبك بنجاح 🎉',
            'ملغي'         => 'عذراً! تم إلغاء طلبك.',
        ];
        $messageText = $statusMessages[$request->status] ?? 'تم تحديث حالة طلبك.';

        app(NotificationController::class)->sendOrderStatusNotification(new Request([
            'profile_id' => $order->user_id,
            'order_id'   => $order->id,
            'status'     => $messageText,
        ]));

        // ── Handle "تم التوصيل" → award targets & monthly targets ──────────
        if ($order->status === 'تم التوصيل' && $oldStatus !== 'تم التوصيل') {

            $wallet = $order->user->wallet;
            if (!$wallet) {
                $wallet = $order->user->wallet()->create(['balance' => 0]);
            }

            // ── Non-monthly Target: once per month per target ───────────────
            // A user can earn each (non-monthly) target only once per calendar month.
            $currentMonth = now()->month;
            $currentYear  = now()->year;

            // Find already earned target IDs this month
            $earnedTargetIdsThisMonth = UserTarget::where('user_id', $order->user_id)
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->pluck('target_id')
                ->toArray();

            // Find the best single target the order qualifies for (highest unearned target first)
            $targets = Target::orderBy('points', 'desc')->get();
            foreach ($targets as $target) {
                if ($target->goal <= $order->total_price) {
                    // Check the user has NOT already earned this specific target this month
                    if (!in_array($target->id, $earnedTargetIdsThisMonth)) {
                        $wallet->refresh();
                        $wallet->update(['balance' => $wallet->balance + $target->points]);

                        UserTarget::create([
                            'user_id'   => $order->user_id,
                            'target_id' => $target->id,
                            'order_id'  => $order->id,
                        ]);

                        app(NotificationController::class)->sendOrderStatusNotification(new Request([
                            'profile_id' => $order->user_id,
                            'order_id'   => $order->id,
                            'title'      => '🎯 مكافأة نقاط',
                            'status'     => 'تهانينا! لقد ربحت ' . $target->points . ' نقطة',
                            'type'       => 'target_reward',
                        ]));

                        break; // Award the highest unearned qualifying target and exit loop
                    }
                }
            }

            // ── Monthly Target: accumulated order total for the month ────────
            $profile = $order->user->profile;
            $newTotal = Order::where('user_id', $order->user_id)
                ->where('status', 'تم التوصيل')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->sum('total_price');

            if (!$profile) {
                $profile = $order->user->profile()->create([
                    'total_orders_price_in_current_month' => $newTotal,
                ]);
            } else {
                $profile->update(['total_orders_price_in_current_month' => $newTotal]);
            }

            // Find which monthly targets have already been achieved this month
            $achievedMonthlyTargetIds = UserMonthlyTarget::where('user_id', $order->user_id)
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->pluck('monthly_target_id')
                ->toArray();

            // Award all monthly targets now newly achieved (ordered ascending so lowest is first)
            $unachievedMonthlyTargets = MonthlyTarget::whereNotIn('id', $achievedMonthlyTargetIds)
                ->orderBy('goal', 'asc')
                ->get();

            foreach ($unachievedMonthlyTargets as $monthlyTarget) {
                if ($newTotal >= $monthlyTarget->goal) {
                    $wallet->refresh();
                    $wallet->update(['balance' => $wallet->balance + $monthlyTarget->points]);

                    UserMonthlyTarget::create([
                        'user_id'           => $order->user_id,
                        'monthly_target_id' => $monthlyTarget->id,
                        'order_id'          => $order->id,
                    ]);

                    app(NotificationController::class)->sendOrderStatusNotification(new Request([
                        'profile_id' => $order->user_id,
                        'order_id'   => $order->id,
                        'title'      => '🏆 هدف شهري محقق',
                        'status'     => 'تهانينا! لقد حققت الهدف الشهري وربحت ' . $monthlyTarget->points . ' نقطة',
                        'type'       => 'monthly_target_reward',
                    ]));
                }
            }
        }

        // ── Handle "ملغي" → reverse rewards if was previously delivered ────
        if ($order->status === 'ملغي') {

            // Refund wallet discount
            if ($order->discount_amount > 0) {
                $wallet = Wallet::where('user_id', $order->user_id)->first();
                if ($wallet) {
                    $wallet->update(['balance' => $wallet->balance + $order->discount_amount]);
                }
                $order->update(['discount_amount' => 0]);
            }

            // If it was previously delivered, reverse the awarded targets
            if ($oldStatus === 'تم التوصيل') {

                // Reverse non-monthly target for this order using order_id
                $userTarget = UserTarget::where('order_id', $order->id)->first();
                if ($userTarget) {
                    $target = $userTarget->target;
                    if ($target) {
                        $wallet = $order->user->wallet;
                        if ($wallet) {
                            $wallet->update([
                                'balance' => max(0, $wallet->balance - $target->points),
                            ]);
                        }
                    }
                    $userTarget->delete();
                }

                // Recalculate monthly total for current month
                $currentMonth = now()->month;
                $currentYear  = now()->year;

                $newTotal = Order::where('user_id', $order->user_id)
                    ->where('status', 'تم التوصيل')
                    ->whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->sum('total_price');

                $profile = $order->user->profile;
                if ($profile) {
                    $profile->update(['total_orders_price_in_current_month' => $newTotal]);
                }

                // Revoke any monthly targets earned this month if new total is below target goal
                $userMonthlyTargets = UserMonthlyTarget::where('user_id', $order->user_id)
                    ->whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->get();

                foreach ($userMonthlyTargets as $userMonthlyTarget) {
                    $monthlyTarget = $userMonthlyTarget->monthlyTarget;
                    if ($monthlyTarget && $newTotal < $monthlyTarget->goal) {
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
            'status_code' => 200,
            'message'     => 'تم تحديث الطلب بنجاح',
            'data'        => $order,
        ]);
    }

    public function updateCustomerOrder($id, Request $request)
    {
        $user = auth()->user();
        $order = Order::with('products')->find($id);

        if (!$order) {
            return $this->errorResponse('الطلب غير موجود', 404);
        }

        if ($order->user_id !== $user->id && !$user->isAdmin()) {
            return $this->errorResponse('غير مصرح لك بتعديل هذا الطلب', 403);
        }

        $allowedStatuses = ['قيد الانتظار', 'تم التاكيد', 'جاري التجهيز', 'جديد'];
        if (!in_array($order->status, $allowedStatuses)) {
            return $this->errorResponse('لا يمكن تعديل الطلب في حالته الحالية (' . $order->status . ')', 400);
        }

        $items = $request->input('items', []);
        if (empty($items) || !is_array($items)) {
            return $this->errorResponse('يجب اختيار منتج واحد على الأقل في الطلب', 400);
        }

        $user->load('profile.region');
        $region = $user->profile->region ?? null;

        $minOrderTotalPrice = ($region && $region->min_order_price !== null) 
            ? (float) $region->min_order_price 
            : 0.0;

        $minOrderProductsCount = ($region && $region->min_order_products !== null) 
            ? (int) $region->min_order_products 
            : 0;

        $newItemsTotal = 0;
        $syncData = [];
        $validItemsCount = 0;

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = (int) ($item['number_of_units'] ?? $item['quantity'] ?? 0);
            if (!$productId || $quantity <= 0) {
                continue;
            }

            $product = Product::find($productId);
            if (!$product) {
                continue;
            }

            if ($product->max_quantity !== null && $product->max_quantity > 0 && $quantity > (int) $product->max_quantity) {
                return $this->errorResponse('لا يمكن إضافة أكثر من ' . $product->max_quantity . ' من المنتج: ' . $product->name, 400);
            }

            $unitPrice = (float) ($product->unit_price ?? $product->price ?? 0);
            $totalProductPrice = $unitPrice * $quantity;
            $newItemsTotal += $totalProductPrice;
            $validItemsCount++;

            $syncData[$productId] = [
                'number_of_units'     => $quantity,
                'unit_price'          => $unitPrice,
                'total_product_price' => $totalProductPrice,
            ];
        }

        if (empty($syncData)) {
            return $this->errorResponse('المنتجات المحددة غير صالحة', 400);
        }

        if ($newItemsTotal < $minOrderTotalPrice) {
            return $this->errorResponse('سعر الطلب المعدل أقل من الحد الأدنى للمنطقة (' . $minOrderTotalPrice . ' ج.م)', 400);
        }

        if ($validItemsCount < $minOrderProductsCount) {
            return $this->errorResponse('عدد المنتجات في الطلب أقل من الحد الأدنى للمنطقة (' . $minOrderProductsCount . ' منتج)', 400);
        }

        $discountAmount = (float) $order->discount_amount;
        $totalPrice = $newItemsTotal;

        if ($discountAmount > 0) {
            if ($newItemsTotal < $discountAmount) {
                $excessRefund = $discountAmount - $newItemsTotal;
                $wallet = $user->wallet;
                if ($wallet) {
                    $wallet->update(['balance' => $wallet->balance + $excessRefund]);
                }
                $discountAmount = $newItemsTotal;
                $totalPrice = 0;
            } else {
                $totalPrice = $newItemsTotal - $discountAmount;
            }
        }

        try {
            DB::transaction(function () use ($order, $totalPrice, $discountAmount, $syncData) {
                $order->update([
                    'total_price'           => $totalPrice,
                    'discount_amount'       => $discountAmount,
                    'is_edited_by_customer' => true,
                ]);

                $order->products()->sync($syncData);
            });

            $order->load(['products', 'user.profile']);

            $adminsAndSubAdmins = User::whereIn('role', ['admin', 'sub_admin'])->get();
            $notificationMsg = 'قام العميل ' . $user->name . ' بتعديل الطلب رقم #' . $order->id . ' (تم تعديله بواسطة العميل)';

            foreach ($adminsAndSubAdmins as $receiver) {
                try {
                    app(NotificationController::class)->sendOrderStatusNotification(new Request([
                        'profile_id' => $receiver->id,
                        'order_id'   => $order->id,
                        'title'      => '✏️ تم تعديل طلب بواسطة العميل',
                        'status'     => $notificationMsg,
                        'type'       => 'order_edited',
                    ]));
                } catch (\Exception $e) {
                    Log::error("Failed to notify user id={$receiver->id}: " . $e->getMessage());
                }
            }

            return $this->successResponse([
                'status_code' => 200,
                'message'     => 'تم تعديل الطلب بنجاح',
                'data'        => $order,
            ]);
        } catch (\Exception $e) {
            Log::error('Order Update Failed: ' . $e->getMessage());
            return $this->errorResponse('حدث خطأ أثناء تعديل الطلب', 500);
        }
    }

    public function cancelCustomerOrder($id, Request $request)
    {
        $user = auth()->user();
        $order = Order::find($id);

        if (!$order) {
            return $this->errorResponse('الطلب غير موجود', 404);
        }

        if ($order->user_id !== $user->id && !$user->isAdmin()) {
            return $this->errorResponse('غير مصرح لك بإلغاء هذا الطلب', 403);
        }

        $allowedStatuses = ['قيد الانتظار', 'تم التاكيد', 'جاري التجهيز', 'جديد'];
        if (!in_array($order->status, $allowedStatuses)) {
            return $this->errorResponse('لا يمكن إلغاء الطلب في حالته الحالية (' . $order->status . ')', 400);
        }

        try {
            DB::transaction(function () use ($order, $user) {
                if ($order->discount_amount > 0) {
                    $wallet = Wallet::where('user_id', $order->user_id)->first();
                    if ($wallet) {
                        $wallet->update(['balance' => $wallet->balance + $order->discount_amount]);
                    }
                    $order->discount_amount = 0;
                }

                $order->status = 'ملغي';
                $order->save();
            });

            $adminsAndSubAdmins = User::whereIn('role', ['admin', 'sub_admin'])->get();
            $notificationMsg = 'قام العميل ' . $user->name . ' بإلغاء/حذف الطلب رقم #' . $order->id;

            foreach ($adminsAndSubAdmins as $receiver) {
                try {
                    app(NotificationController::class)->sendOrderStatusNotification(new Request([
                        'profile_id' => $receiver->id,
                        'order_id'   => $order->id,
                        'title'      => '❌ تم إلغاء/حذف طلب بواسطة العميل',
                        'status'     => $notificationMsg,
                        'type'       => 'order_deleted',
                    ]));
                } catch (\Exception $e) {
                    Log::error("Failed to notify user id={$receiver->id}: " . $e->getMessage());
                }
            }

            return $this->successResponse([
                'status_code' => 200,
                'message'     => 'تم إلغاء الطلب بنجاح',
                'data'        => $order,
            ]);
        } catch (\Exception $e) {
            Log::error('Order Cancellation Failed: ' . $e->getMessage());
            return $this->errorResponse('حدث خطأ أثناء إلغاء الطلب', 500);
        }
    }

    public function updateAdminOrder($id, Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $user->role !== 'sub_admin') {
            return $this->errorResponse('غير مصرح لك بتعديل الطلب كأدمن', 403);
        }

        $order = Order::with('products')->find($id);
        if (!$order) {
            return $this->errorResponse('الطلب غير موجود', 404);
        }

        if ($order->status === 'تم التوصيل' || $order->status === 'تم التسليم' || $order->status === 'ملغي' || $order->status === 'ملغاة') {
            return $this->errorResponse('لا يمكن تعديل الطلب بعد تم توصيله أو إلغائه', 400);
        }

        $items = $request->input('items', []);
        if (empty($items) || !is_array($items)) {
            return $this->errorResponse('يجب اختيار منتج واحد على الأقل في الطلب', 400);
        }

        $newItemsTotal = 0;
        $syncData = [];

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = (int) ($item['number_of_units'] ?? $item['quantity'] ?? 0);
            if (!$productId || $quantity <= 0) {
                continue;
            }

            $product = Product::find($productId);
            if (!$product) {
                continue;
            }

            $unitPrice = (float) ($product->unit_price ?? $product->price ?? 0);
            $totalProductPrice = $unitPrice * $quantity;
            $newItemsTotal += $totalProductPrice;

            $syncData[$productId] = [
                'number_of_units'     => $quantity,
                'unit_price'          => $unitPrice,
                'total_product_price' => $totalProductPrice,
            ];
        }

        if (empty($syncData)) {
            return $this->errorResponse('المنتجات المحددة غير صالحة', 400);
        }

        $discountAmount = (float) $order->discount_amount;
        $totalPrice = $newItemsTotal;

        if ($discountAmount > 0) {
            if ($newItemsTotal < $discountAmount) {
                $excessRefund = $discountAmount - $newItemsTotal;
                $wallet = Wallet::where('user_id', $order->user_id)->first();
                if ($wallet) {
                    $wallet->update(['balance' => $wallet->balance + $excessRefund]);
                }
                $discountAmount = $newItemsTotal;
                $totalPrice = 0;
            } else {
                $totalPrice = $newItemsTotal - $discountAmount;
            }
        }

        try {
            DB::transaction(function () use ($order, $totalPrice, $discountAmount, $syncData) {
                $order->update([
                    'total_price'        => $totalPrice,
                    'discount_amount'    => $discountAmount,
                    'is_edited_by_admin' => true,
                ]);

                $order->products()->sync($syncData);
            });

            $order->load(['products', 'user.profile']);

            try {
                app(NotificationController::class)->sendOrderStatusNotification(new Request([
                    'profile_id' => $order->user_id,
                    'order_id'   => $order->id,
                    'title'      => '✏️ تم تعديل طلبك بواسطة الإدارة',
                    'status'     => 'تم تعديل منتجات طلبك رقم #' . $order->id . ' بواسطة الإدارة',
                    'type'       => 'order_edited_by_admin',
                ]));
            } catch (\Exception $e) {
                Log::error("Failed to notify customer id={$order->user_id}: " . $e->getMessage());
            }

            return $this->successResponse([
                'status_code' => 200,
                'message'     => 'تم تعديل الطلب بواسطة الإدارة بنجاح',
                'data'        => $order,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Order Update Failed: ' . $e->getMessage());
            return $this->errorResponse('حدث خطأ أثناء تعديل الطلب بواسطة الإدارة', 500);
        }
    }
}
