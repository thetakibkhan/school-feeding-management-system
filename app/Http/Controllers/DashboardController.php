<?php

namespace App\Http\Controllers;

use App\Repositories\DailyDeliveryReportRepository;
use App\Services\DailyDeliveryReportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DailyDeliveryReportService $dailyReports,
        DailyDeliveryReportRepository $reportRepository,
    ): View {
        $today = today()->toDateString();
        $isAdmin = $request->user()->isAdmin();
        $dashboardReport = $isAdmin ? $dailyReports->forDate($today) : null;
        $shortfallRows = $dashboardReport['shortfallRows'] ?? [];
        $shortfallsPage = LengthAwarePaginator::resolveCurrentPage('shortfalls');
        $shortfallSchools = new LengthAwarePaginator(
            array_slice($shortfallRows, ($shortfallsPage - 1) * 10, 10),
            count($shortfallRows),
            10,
            $shortfallsPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'shortfalls',
                'query' => $request->query(),
            ],
        );

        return view('dashboard', [
            'today' => $today,
            'dashboardStatus' => $dashboardReport['status'] ?? $dailyReports->statusForDate($today),
            'dashboardReport' => $dashboardReport,
            'shortfallSchools' => $shortfallSchools,
            'ownDeliveries' => $isAdmin
                ? collect()
                : $reportRepository->paginateDeliveriesForCreatorOnDate($request->user(), $today),
        ]);
    }
}
