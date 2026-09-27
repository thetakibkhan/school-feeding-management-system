<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\OfficialReportPeriod;
use App\Models\School;
use App\Models\StockReportPeriod;
use App\Services\FormThirteenTemplate;
use App\Services\FormTwelveTemplate;
use App\Services\StockFormPdfWriter;
use App\Services\StockReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockFormController extends Controller
{
    public function __construct(
        private readonly StockReportService $reports,
        private readonly FormTwelveTemplate $formTwelve,
        private readonly FormThirteenTemplate $formThirteen,
        private readonly StockFormPdfWriter $pdfWriter,
    ) {}

    public function information(Request $request, string $form): View
    {
        $month = $this->month($request);
        $type = $this->formType($form);
        if ($form === '12') {
            $schools = School::query()->orderBy('school_code')->get(['id', 'school_code', 'name']);
            $schoolId = (string) $request->query('school_id', 'all');

            return view('reports.official.form-twelve-select', compact('month', 'schools', 'schoolId'));
        }

        $report = $this->reports->forMonth($month);
        $completedCount = count(array_filter($report['schools'], fn (array $school): bool => $this->readySchool($form, $school)));
        $period = StockReportPeriod::query()->where('form_type', $type)->where('month', $month)->first();
        $selected = collect($report['schools'])->firstWhere('school.id', (int) $request->query('school_id')) ?? ($report['schools'][0] ?? null);
        $supplier = OfficialReportPeriod::query()->where('month', $month)->value('supplier_name');

        return view('reports.official.stock-information', compact('form', 'month', 'report', 'period', 'selected', 'supplier', 'completedCount'));
    }

    public function savePeriod(Request $request, string $form): RedirectResponse
    {
        if ($form === '12') {
            return to_route('admin.reports.stock.information', ['form' => $form, 'month' => $this->month($request)]);
        }
        $type = $this->formType($form);
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'district_name' => ['required', 'string', 'max:255'],
            'upazila_name' => ['required', 'string', 'max:255'],
            'supplier_name' => [$form === '13' ? 'required' : 'nullable', 'string', 'max:255'],
        ]);
        StockReportPeriod::query()->updateOrCreate(
            ['form_type' => $type, 'month' => $data['month']],
            ['district_name' => $data['district_name'], 'upazila_name' => $data['upazila_name'],
                'supplier_name' => $form === '13' ? $data['supplier_name'] : null],
        );

        return to_route('admin.reports.stock.information', ['form' => $form, 'month' => $data['month']])
            ->with('status', 'Report information saved.');
    }

    public function saveSchool(Request $request, string $form, School $school): RedirectResponse
    {
        abort_if($form === '12', 404);
        $this->formType($form);
        $rules = [
            'month' => ['required', 'date_format:Y-m'],
            'boy_count' => ['nullable', 'integer', 'min:0'],
            'girl_count' => ['nullable', 'integer', 'min:0'],
            'union_name' => ['nullable', 'string', 'max:255'],
            'cluster_name' => ['nullable', 'string', 'max:255'],
        ];
        $items = $form === '12' ? ['bun', 'egg', 'banana', 'biscuit', 'milk'] : ['bun', 'egg', 'banana'];
        foreach ($items as $item) {
            $rules[$item.'_opening'] = ['required', 'integer', 'min:0'];
            $rules[$item.'_distributed'] = ['required', 'integer', 'min:0'];
        }
        if ($form === '12') {
            foreach (['biscuit', 'milk'] as $item) {
                $rules[$item.'_received'] = ['required', 'integer', 'min:0'];
            }
        }
        $data = $request->validate($rules);
        $month = $data['month'];
        unset($data['month']);
        $this->reports->saveSchoolInputs($school, $month, $data);

        return to_route('admin.reports.stock.information', ['form' => $form, 'month' => $month, 'school_id' => $school->id])
            ->with('status', 'School stock information saved.');
    }

    public function preview(Request $request, string $form): View|RedirectResponse
    {
        $month = $this->month($request);
        $type = $this->formType($form);
        if ($form === '12') {
            $schoolId = (string) $request->query('school_id', 'all');
            $report = $this->selectedSchoolReport($this->reports->forMonth($month), $schoolId);
            if (! $this->ready($form, $report)) {
                return to_route('admin.reports.stock.information', ['form' => $form, 'month' => $month, 'school_id' => $schoolId])
                    ->withErrors('Required Form 12 data is missing for the selected school or month.');
            }
            $period = StockReportPeriod::query()->where('form_type', $type)->where('month', $month)->first()
                ?? new StockReportPeriod(['district_name' => 'চট্টগ্রাম', 'upazila_name' => 'আনোয়ারা']);
            $pages = $this->pages($form, $report, $period);
            $ready = true;

            return view('reports.official.stock-preview', compact('form', 'month', 'report', 'pages', 'ready', 'schoolId'));
        }

        $period = StockReportPeriod::query()->where('form_type', $type)->where('month', $month)->first();
        if (! $period) {
            return to_route('admin.reports.stock.information', ['form' => $form, 'month' => $month])
                ->withErrors('Save report information before opening the preview.');
        }
        $report = $this->reports->forMonth($month);
        $pages = $this->pages($form, $report, $period);
        $ready = $this->ready($form, $report);
        $schoolId = 'all';

        return view('reports.official.stock-preview', compact('form', 'month', 'report', 'pages', 'ready', 'schoolId'));
    }

    public function pdf(Request $request, string $form): Response
    {
        $month = $this->month($request);
        $type = $this->formType($form);
        $schoolId = (string) $request->query('school_id', 'all');
        $report = $this->reports->forMonth($month);
        if ($form === '12') {
            $report = $this->selectedSchoolReport($report, $schoolId);
            $period = StockReportPeriod::query()->where('form_type', $type)->where('month', $month)->first()
                ?? new StockReportPeriod(['district_name' => 'চট্টগ্রাম', 'upazila_name' => 'আনোয়ারা']);
        } else {
            $period = StockReportPeriod::query()->where('form_type', $type)->where('month', $month)->first();
        }
        if (! $period || ! $this->ready($form, $report)) {
            throw ValidationException::withMessages(['report' => 'Save all required report and school stock information before downloading the official PDF.']);
        }

        return response($this->pdfWriter->render($this->pages($form, $report, $period)), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="form-'.$form.'-'.$month.'.pdf"',
        ]);
    }

    /** @param array<string, mixed> $report
     * @return list<array{artwork: string, overlays: list<array<string, int|string>>}>
     */
    private function pages(string $form, array $report, StockReportPeriod $period): array
    {
        if ($form === '13') {
            if ($report['school_count'] > 110) {
                throw ValidationException::withMessages(['report' => 'Form 13 reference has room for 110 schools.']);
            }

            return $this->formThirteen->pages($report, $period);
        }

        return array_map(fn (array $page): array => [
            'artwork' => 'form-12/page-1.png',
            'overlays' => $this->formTwelve->overlays($report, $period, $page),
        ], $report['schools']);
    }

    private function month(Request $request): string
    {
        return $request->validate(['month' => ['sometimes', 'date_format:Y-m']])['month'] ?? '2026-09';
    }

    /** @param array<string, mixed> $report */
    private function ready(string $form, array $report): bool
    {
        if ($report['school_count'] === 0) {
            return false;
        }
        foreach ($report['schools'] as $school) {
            if (! $this->readySchool($form, $school)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $school */
    private function readySchool(string $form, array $school): bool
    {
        if ($school['missing_delivery_count'] > 0) {
            return false;
        }
        $items = $form === '12' ? ['milk', 'biscuit', 'bun', 'egg', 'banana'] : ['bun', 'egg', 'banana'];
        foreach ($items as $item) {
            if ($school['items'][$item]['closing'] === null || $school['items'][$item]['closing'] < 0) {
                return false;
            }
        }
        if ($form === '12') {
            $input = $school['input'];

            return $school['student_count'] !== null
                && $input?->boy_count !== null
                && $input?->girl_count !== null
                && $input->boy_count + $input->girl_count === $school['student_count'];
        }

        return true;
    }

    /** @param array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function selectedSchoolReport(array $report, string $schoolId): array
    {
        if ($schoolId === 'all') {
            return $report;
        }

        Validator::make(['school_id' => $schoolId], [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
        ])->validate();

        $report['schools'] = array_values(array_filter(
            $report['schools'],
            fn (array $entry): bool => (string) $entry['school']->id === $schoolId,
        ));
        $report['school_count'] = count($report['schools']);

        return $report;
    }

    private function formType(string $form): string
    {
        abort_unless(in_array($form, ['12', '13'], true), 404);

        return 'form_'.$form;
    }
}
