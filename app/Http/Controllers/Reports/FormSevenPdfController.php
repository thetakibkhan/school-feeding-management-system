<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\FormSevenPdfRenderer;
use App\Services\FormSevenReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class FormSevenPdfController extends Controller
{
    public function __invoke(Request $request, FormSevenReportService $forms, FormSevenPdfRenderer $renderer): Response
    {
        $validated = $request->validate([
            'month' => ['sometimes', 'required', 'date_format:Y-m'],
        ]);
        $month = $validated['month'] ?? now()->format('Y-m');
        $form = $forms->forMonth($month);

        if ($form['missing_reasons'] !== []) {
            throw ValidationException::withMessages([
                'month' => implode(' ', $form['missing_reasons']).' Complete the required records before generating the official PDF.',
            ]);
        }

        return response($renderer->render($form), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="form-7-'.$month.'.pdf"',
        ]);
    }
}
