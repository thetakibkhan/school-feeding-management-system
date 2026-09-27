<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\FormTenReportService;
use App\Services\FormTenTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormTenReportController extends Controller
{
    public function __invoke(Request $request, FormTenReportService $forms, FormTenTemplate $template): View
    {
        $validated = $request->validate([
            'month' => ['sometimes', 'required', 'date_format:Y-m'],
        ]);
        $form = $forms->forMonth($validated['month'] ?? '2026-09');

        return view('reports.official.form-ten', [
            'form' => $form,
            'overlays' => $template->overlays($form),
        ]);
    }
}
