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
    public function getStatistics(Request $request)
    {
        $filter = $request->query('filter', 'this_month'); // this_week | this_month | last_month

        [$startDate, $endDate] = $this->getDateRange($filter);

        $categoriesCount = Category::count();
        $productsCount   = Product::count();

        $totalCustomers = User::where('role', 'customer')->count();

        $totalCustomersInPeriod = User::where('role', 'customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalSumOfOrdersTotalPrice = (float) Order::sum('total_price');

        $totalSumOfOrdersTotalPriceInPeriod = (float) Order::whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_price');

        $statuses = ["جاري التجهيز", "تم التوصيل", "ملغي"];

        // All-time by status
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

        // Period by status
        $totalSumOfOrdersByStatusInPeriod = array_fill_keys($statuses, 0.0);
        $orderSumsByStatusInPeriod = Order::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->select('status', DB::raw('SUM(total_price) as total_sum'))
            ->pluck('total_sum', 'status')
            ->toArray();
        foreach ($orderSumsByStatusInPeriod as $status => $sum) {
            if (in_array($status, $statuses)) {
                $totalSumOfOrdersByStatusInPeriod[$status] = (float) $sum;
            }
        }

        $data = [
            'filter'                                    => $filter,
            'period_start'                              => $startDate->toDateString(),
            'period_end'                                => $endDate->toDateString(),
            'categories_count'                          => $categoriesCount,
            'products_count'                            => $productsCount,
            'total_customers'                           => $totalCustomers,
            'total_customers_registered_in_period'      => $totalCustomersInPeriod,
            'total_sum_of_orders_total_price'           => $totalSumOfOrdersTotalPrice,
            'total_sum_of_orders_total_price_in_period' => $totalSumOfOrdersTotalPriceInPeriod,
            'total_sum_of_orders_by_status'             => $totalSumOfOrdersByStatus,
            'total_sum_of_orders_by_status_in_period'   => $totalSumOfOrdersByStatusInPeriod,
        ];

        return $this->successResponse(
            data: $data,
            message: "تم جلب الإحصائيات بنجاح",
            statusCode: 200
        );
    }

    private function getDateRange(string $filter): array
    {
        switch ($filter) {
            case 'this_week':
                return [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ];
            case 'last_month':
                return [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth(),
                ];
            case 'this_month':
            default:
                return [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ];
        }
    }
}
