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

        // ── Global Counts ────────────────────────────────────────────────
        $categoriesCount = Category::count();
        $productsCount   = Product::count();
        $totalCustomers  = User::where('role', 'customer')->count();

        // ── Period-Scoped Counts & Orders ──────────────────────────────
        $periodCustomersCount = User::where('role', 'customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $statuses = ['قيد الانتظار', 'تم التاكيد', 'تم الشحن', 'تم التوصيل', 'ملغي'];

        // Period aggregate stats
        $orderStats = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('COALESCE(SUM(total_price), 0) as orders_sum')
            )
            ->first();

        $ordersCount    = (int)   ($orderStats->orders_count ?? 0);
        $ordersTotalSum = (float) ($orderStats->orders_sum ?? 0);

        // All-time aggregate stats
        $allOrderStats = Order::select(
            DB::raw('COUNT(*) as orders_count'),
            DB::raw('COALESCE(SUM(total_price), 0) as orders_sum')
        )->first();

        $allOrdersTotalSum = (float) ($allOrderStats->orders_sum ?? 0);

        // ── Status breakdown for Period ─────────────────────────────────
        $ordersSumByStatus   = array_fill_keys($statuses, 0.0);
        $ordersCountByStatus = array_fill_keys($statuses, 0);
        $rawPeriodStatusSum  = [];

        $byStatus = Order::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->select(
                'status',
                DB::raw('COUNT(*) as cnt'),
                DB::raw('COALESCE(SUM(total_price), 0) as total_sum')
            )
            ->get();

        foreach ($byStatus as $row) {
            $rawStatus = $row->status;
            $rawPeriodStatusSum[$rawStatus] = (float) $row->total_sum;

            $st = $rawStatus;
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

        // ── Status breakdown for All-time ───────────────────────────────
        $rawAllStatusSum = [];
        $allByStatus = Order::groupBy('status')
            ->select(
                'status',
                DB::raw('COALESCE(SUM(total_price), 0) as total_sum')
            )
            ->get();

        foreach ($allByStatus as $row) {
            $rawAllStatusSum[$row->status] = (float) $row->total_sum;
        }

        // ── Fetch actual orders for the period ─────────────────────────
        $periodOrders = Order::with(['products', 'user.profile'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        // ── Daily Breakdown for Charts ─────────────────────────────────
        $dailyBreakdown = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('COALESCE(SUM(total_price), 0) as sales')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date'         => $item->date,
                    'orders_count' => (int) $item->orders_count,
                    'sales'        => (float) $item->sales,
                ];
            });

        // ── Response ─────────────────────────────────────────────────────
        $data = [
            'filter'                                      => $filter,
            'period_start'                                => $startDate->toDateString(),
            'period_end'                                  => $endDate->toDateString(),
            'categories_count'                            => $categoriesCount,
            'products_count'                              => $productsCount,
            'customers_count'                             => $periodCustomersCount,
            'period_customers_count'                      => $periodCustomersCount,
            'total_customers'                             => $totalCustomers,
            'total_customers_registered_this_month'       => $periodCustomersCount,
            'orders_count'                                => $ordersCount,
            'orders_total_sum'                            => $ordersTotalSum,
            'total_sum_of_orders_total_price'             => $allOrdersTotalSum,
            'total_sum_of_orders_total_price_this_month'  => $ordersTotalSum,
            'orders_sum_by_status'                        => $ordersSumByStatus,
            'orders_count_by_status'                      => $ordersCountByStatus,
            'total_sum_of_orders_by_status'               => $rawAllStatusSum,
            'total_sum_of_orders_by_status_this_month'    => $rawPeriodStatusSum,
            'orders'                                      => $periodOrders,
            'daily_breakdown'                             => $dailyBreakdown,
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

