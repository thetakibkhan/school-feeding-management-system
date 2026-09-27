<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\FormTenPdfRenderer;
use App\Services\FormTenReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FormTenPdfController extends Controller
{
    public function __invoke(Request $request, FormTenReportService $forms, FormTenPdfRenderer $renderer): Response
    {
        $validated = $request->validate([
            'month' => ['sometimes', 'required', 'date_format:Y-m'],
        ]);
        $month = $validated['month'] ?? '2026-09';

        return response($renderer->render($forms->forMonth($month)), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="form-10-'.$month.'.pdf"',
        ]);
    }
}
