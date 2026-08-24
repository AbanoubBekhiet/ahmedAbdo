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
        $filter = $request->input('filter', 'this_month');
        $validFilters = ['this_month', 'last_month', 'this_year', 'custom'];

        if (!in_array($filter, $validFilters) && !($request->has('period_start') && $request->has('period_end'))) {
            return $this->errorResponse(
                'الفلتر غير صالح. القيم المتاحة: this_month, last_month, this_year, custom',
                400
            );
        }

        [$startDate, $endDate] = $this->getDateRange($request, $filter);

        // ── Global counts ────────────────────────────────────────────────
        $categoriesCount = Category::count();
        $productsCount   = Product::count();

        // ── Period-scoped stats ──────────────────────────────────────────
        $customersCount = User::where('role', 'customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $statuses = ['قيد الانتظار', 'تم التاكيد', 'تم الشحن', 'تم التوصيل', 'ملغي'];

        $orderStats = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('COALESCE(SUM(total_price), 0) as orders_sum')
            )
            ->first();

        $ordersCount    = (int)   $orderStats->orders_count;
        $ordersTotalSum = (float) $orderStats->orders_sum;

        // ── Period by status ─────────────────────────────────────────────
        $ordersSumByStatus   = array_fill_keys($statuses, 0.0);
        $ordersCountByStatus = array_fill_keys($statuses, 0);

        $byStatus = Order::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->select(
                'status',
                DB::raw('COUNT(*) as cnt'),
                DB::raw('COALESCE(SUM(total_price), 0) as total_sum')
            )
            ->get();

        foreach ($byStatus as $row) {
            $st = $row->status;
            if (in_array($st, ['جديد', 'new', 'جاري التجهيز', 'جاري التحضير', 'قيد التحضير'])) {
                $st = 'قيد الانتظار';
            } elseif (in_array($st, ['تم التسليم', 'ناجحة'])) {
                $st = 'تم التوصيل';
            } elseif ($st === 'ملغاة') {
                $st = 'ملغي';
            }

            if (isset($ordersCountByStatus[$st])) {
                $ordersSumByStatus[$st]   += (float) $row->total_sum;
                $ordersCountByStatus[$st] += (int)   $row->cnt;
            }
        }

        // ── Response ─────────────────────────────────────────────────────
        $data = [
            'filter'                  => $filter,
            'period_start'            => $startDate->toDateString(),
            'period_end'              => $endDate->toDateString(),
            'categories_count'        => $categoriesCount,
            'products_count'          => $productsCount,
            'customers_count'         => $customersCount,
            'orders_count'            => $ordersCount,
            'orders_total_sum'        => $ordersTotalSum,
            'orders_sum_by_status'    => $ordersSumByStatus,
            'orders_count_by_status'  => $ordersCountByStatus,
        ];

        return $this->successResponse(
            data: $data,
            message: 'تم جلب الإحصائيات بنجاح',
            statusCode: 200
        );
    }

    private function getDateRange(Request $request, string $filter): array
    {
        $now = Carbon::now();

        if ($filter === 'custom' || ($request->has('period_start') && $request->has('period_end'))) {
            $start = $request->input('period_start');
            $end   = $request->input('period_end');
            if ($start && $end) {
                return [
                    Carbon::parse($start)->startOfDay(),
                    Carbon::parse($end)->endOfDay(),
                ];
            }
        }

        switch ($filter) {
            case 'last_month':
                return [
                    $now->copy()->subMonthNoOverflow()->startOfMonth(),
                    $now->copy()->subMonthNoOverflow()->endOfMonth(),
                ];
            case 'this_year':
                return [
                    $now->copy()->startOfYear(),
                    $now->copy()->endOfYear(),
                ];
            case 'this_month':
            default:
                return [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                ];
        }
    }
}

