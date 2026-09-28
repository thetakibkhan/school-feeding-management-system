<?php

namespace App\Http\Controllers\Reports;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\DailyDeliveryReportRequest;
use App\Models\FoodItem;
use App\Services\DailyDeliveryReportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyDeliveryReportController extends Controller
{
    public function index(
        DailyDeliveryReportRequest $request,
        DailyDeliveryReportService $dailyReports,
    ): View {
        return view('reports.daily-delivery.index', [
            'report' => $dailyReports->forDate($request->selectedDate()),
            'canViewChalanPhotos' => $request->user()?->role === UserRole::Admin,
        ]);
    }

    public function export(
        DailyDeliveryReportRequest $request,
        DailyDeliveryReportService $dailyReports,
    ): StreamedResponse {
        $report = $dailyReports->forDate($request->selectedDate());

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $this->headers($report['foodItems']), ',', '"', '');

            if ($report['status'] !== 'working') {
                $message = $report['status'] === 'off_day'
                    ? 'Off-day. Demand is zero for every item.'
                    : 'Date not set up. No working schedule or non-working date is configured.';
                fputcsv($stream, [$message], ',', '"', '');
            }

            foreach ($report['rows'] as $row) {
                $cells = [
                    $row['school']->school_code,
                    $row['school']->emis_code,
                    $row['school']->name,
                    $row['has_delivery'] ? 'Entered' : 'No entry yet',
                ];

                foreach ($report['foodItems'] as $foodItem) {
                    foreach (['demand', 'delivered', 'shortfall', 'excess'] as $measure) {
                        $cells[] = $row['items'][$foodItem->key][$measure];
                    }
                }

                fputcsv($stream, $this->safeCells($cells), ',', '"', '');
            }

            if ($report['status'] === 'working') {
                $totals = ['', '', 'Upazila Total', ''];
                foreach ($report['foodItems'] as $foodItem) {
                    foreach (['demand', 'delivered', 'shortfall', 'excess'] as $measure) {
                        $totals[] = $report['totals'][$foodItem->key][$measure];
                    }
                }
                fputcsv($stream, $totals, ',', '"', '');
            }

            fclose($stream);
        }, 'daily-delivery-'.$report['date'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** @param Collection<int, FoodItem> $foodItems
     * @return list<string>
     */
    private function headers(Collection $foodItems): array
    {
        $headers = ['School code', 'EMIS code', 'School name', 'Entry status'];
        foreach ($foodItems as $foodItem) {
            foreach (['Demand', 'Delivered', 'Shortfall', 'Excess'] as $measure) {
                $headers[] = $foodItem->name.' '.$measure.' ('.$foodItem->unit.')';
            }
        }

        return $headers;
    }

    /** @param list<int|string> $cells
     * @return list<int|string>
     */
    private function safeCells(array $cells): array
    {
        return array_map(function (int|string $cell): int|string {
            if (is_int($cell)) {
                return $cell;
            }

            return preg_match('/^\s*[=+\-@]/u', $cell) === 1 ? "'".$cell : $cell;
        }, $cells);
    }
}
