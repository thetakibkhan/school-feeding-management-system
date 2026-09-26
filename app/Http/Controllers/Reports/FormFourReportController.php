<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\FormFourReportService;
use App\Services\FormFourTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormFourReportController extends Controller
{
    public function __invoke(Request $request, FormFourReportService $forms, FormFourTemplate $template): View
    {
        $validated = $request->validate([
            'month' => ['sometimes', 'required', 'date_format:Y-m'],
        ]);
        $report = $forms->forMonth($validated['month'] ?? '2026-09');
        $year = substr($report['month'], 0, 4);
        $pages = array_map(
            fn (array $schoolPage): array => $schoolPage + [
                'overlays' => $template->overlays($schoolPage, $report['month_label'], $year),
            ],
            $report['schools'],
        );

        return view('reports.official.form-four', [
            'report' => $report,
            'pages' => $pages,
        ]);
    }
}
