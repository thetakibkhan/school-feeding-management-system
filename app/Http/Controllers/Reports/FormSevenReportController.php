<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\FormSevenReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormSevenReportController extends Controller
{
    public function __invoke(Request $request, FormSevenReportService $forms): View
    {
        $validated = $request->validate([
            'month' => ['sometimes', 'required', 'date_format:Y-m'],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');

        return view('reports.official.form-seven', [
            'form' => $forms->forMonth($month),
        ]);
    }
}
