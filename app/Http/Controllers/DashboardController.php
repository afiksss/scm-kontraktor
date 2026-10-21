<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\UsageRequest;
use App\Models\UsageRequestItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $countedStatuses = [PurchaseOrder::APPROVED, PurchaseOrder::PARTIAL, PurchaseOrder::RECEIVED];

        $purchaseMonth = (float) PurchaseOrder::whereIn('status', $countedStatuses)
            ->whereBetween('date', [$monthStart, $monthEnd])->sum('total');
        $usageMonth = $this->releasedQty($monthStart, $monthEnd);

        // Tren 6 bulan terakhir.
        $labels = $purchase = $usage = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i);
            $labels[] = $m->translatedFormat('M Y');
            $purchase[] = round((float) PurchaseOrder::whereIn('status', $countedStatuses)
                ->whereBetween('date', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])->sum('total') / 1_000_000, 1);
            $usage[] = $this->releasedQty($m->copy()->startOfMonth(), $m->copy()->endOfMonth());
        }

        // Distribusi stok per kategori.
        $byCategory = Category::query()
            ->select('categories.name', DB::raw('coalesce(sum(stocks.quantity),0) as qty'))
            ->join('items', 'items.category_id', '=', 'categories.id')
            ->leftJoin('stocks', 'stocks.item_id', '=', 'items.id')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('qty')->get()->filter(fn ($r) => $r->qty > 0)->values();

        $topProjects = UsageRequestItem::query()
            ->join('usage_requests', 'usage_requests.id', '=', 'usage_request_items.usage_request_id')
            ->join('projects', 'projects.id', '=', 'usage_requests.project_id')
            ->where('usage_requests.status', UsageRequest::DONE)
            ->select('projects.name', DB::raw('sum(usage_request_items.qty_released) as qty'))
            ->groupBy('projects.id', 'projects.name')->orderByDesc('qty')->limit(5)->get();

        return view('dashboard', [
            'kpi' => [
                'items' => Item::active()->count(),
                'stock' => (float) Stock::sum('quantity'),
                'purchase' => $purchaseMonth,
                'usage' => $usageMonth,
                'critical' => Item::active()->critical()->count(),
            ],
            'pendingPurchases' => PurchaseOrder::where('status', PurchaseOrder::PENDING)->count(),
            'pendingUsage' => UsageRequest::where('status', UsageRequest::PENDING)->count(),
            'latestPurchases' => PurchaseOrder::with('supplier')->withCount('items')->latest('date')->latest('id')->limit(5)->get(),
            'latestUsage' => UsageRequest::with(['project', 'requester'])->withCount('items')->latest('date')->latest('id')->limit(5)->get(),
            'lowStock' => Item::active()->withTotalStock()->with('unit')->critical()->orderByRaw(Item::TOTAL_SQL . ' / nullif(items.min_stock,0)')->limit(6)->get(),
            'topProjects' => $topProjects,
            'trend' => compact('labels', 'purchase', 'usage'),
            'distribution' => ['labels' => $byCategory->pluck('name'), 'data' => $byCategory->pluck('qty')->map(fn ($v) => (float) $v)],
        ]);
    }

    private function releasedQty(Carbon $from, Carbon $to): float
    {
        return (float) UsageRequestItem::whereHas('usageRequest', fn ($q) => $q
            ->where('status', UsageRequest::DONE)->whereBetween('released_at', [$from, $to]))
            ->sum('qty_released');
    }
}
