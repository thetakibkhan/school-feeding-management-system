<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\FormFourPdfRenderer;
use App\Services\FormFourReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FormFourPdfController extends Controller
{
    public function __invoke(Request $request, FormFourReportService $forms, FormFourPdfRenderer $renderer): Response
    {
        $validated = $request->validate([
            'month' => ['sometimes', 'required', 'date_format:Y-m'],
        ]);
        $month = $validated['month'] ?? '2026-09';

        return response($renderer->render($forms->forMonth($month)), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="form-4-'.$month.'.pdf"',
        ]);
    }
}
