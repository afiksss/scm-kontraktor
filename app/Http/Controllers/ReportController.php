<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\ReportBuilder;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, ReportBuilder $builder)
    {
        $type = $request->query('type', 'pembelian');
        $filters = $request->only(['start', 'end', 'project_id', 'supplier_id', 'warehouse_id', 'category_id']);
        $filters['start'] = $filters['start'] ?? now()->startOfMonth()->toDateString();
        $filters['end'] = $filters['end'] ?? now()->toDateString();

        $request->validate(['start' => 'nullable|date', 'end' => 'nullable|date|after_or_equal:start']);

        $report = $builder->build($type, $filters);

        if ($request->query('export') === 'csv') {
            ActivityLog::record('Laporan', "Mengekspor {$report['title']}");

            return $this->csv($report, $type);
        }

        return view('reports.index', [
            'type' => $type,
            'types' => ReportBuilder::TYPES,
            'filters' => $filters,
            'report' => $report,
            'projects' => Project::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    private function csv(array $report, string $type)
    {
        $filename = $type . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, [$report['title']], ';');
            fputcsv($out, ['Periode', $report['period']], ';');
            fputcsv($out, [], ';');
            fputcsv($out, $report['columns'], ';');
            foreach ($report['rows'] as $row) {
                fputcsv($out, $row, ';');
            }
            fputcsv($out, [], ';');
            foreach ($report['summary'] as [$label, $value]) {
                fputcsv($out, [$label, $value], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
