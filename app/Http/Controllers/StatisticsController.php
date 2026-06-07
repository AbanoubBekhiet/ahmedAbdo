<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    public function getStatistics()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $categoriesCount = Category::count();
        $productsCount = Product::count();

        $totalCustomers = User::where('role', 'customer')->count();

        $totalCustomersThisMonth = User::where('role', 'customer')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        $totalSumOfOrdersTotalPrice = (float) Order::sum('total_price');

        $totalSumOfOrdersTotalPriceThisMonth = (float) Order::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('total_price');

        $statuses = ["جاري التجهيز", "تم التوصيل", "ملغي"];
        
        $totalSumOfOrdersByStatus = array_fill_keys($statuses, 0.0);
        $orderSumsByStatus = Order::groupBy('status')
            ->select('status', DB::raw('SUM(total_price) as total_sum'))
            ->pluck('total_sum', 'status')
            ->toArray();
        foreach ($orderSumsByStatus as $status => $sum) {
            if (in_array($status, $statuses)) {
                $totalSumOfOrdersByStatus[$status] = (float) $sum;
            }
        }

        $totalSumOfOrdersByStatusThisMonth = array_fill_keys($statuses, 0.0);
        $orderSumsByStatusThisMonth = Order::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->groupBy('status')
            ->select('status', DB::raw('SUM(total_price) as total_sum'))
            ->pluck('total_sum', 'status')
            ->toArray();
        foreach ($orderSumsByStatusThisMonth as $status => $sum) {
            if (in_array($status, $statuses)) {
                $totalSumOfOrdersByStatusThisMonth[$status] = (float) $sum;
            }
        }

        $data = [
            'categories_count' => $categoriesCount,
            'products_count' => $productsCount,
            'total_customers' => $totalCustomers,
            'total_customers_registered_this_month' => $totalCustomersThisMonth,
            'total_sum_of_orders_total_price' => $totalSumOfOrdersTotalPrice,
            'total_sum_of_orders_total_price_this_month' => $totalSumOfOrdersTotalPriceThisMonth,
            'total_sum_of_orders_by_status' => $totalSumOfOrdersByStatus,
            'total_sum_of_orders_by_status_this_month' => $totalSumOfOrdersByStatusThisMonth,
        ];

        return $this->successResponse(
            data: $data,
            message: "تم جلب الإحصائيات بنجاح",
            statusCode: 200
        );
    }
}
