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
        $this->formType($form);
        if ($form === '12') {
            $schools = School::query()->orderBy('school_code')->get(['id', 'school_code', 'name']);
            $schoolId = (string) $request->query('school_id', 'all');

            return view('reports.official.form-twelve-select', compact('month', 'schools', 'schoolId'));
        }

        $period = $this->formThirteenPeriod($month);
        $missingMetadata = $this->missingFormThirteenMetadata($period);
        $schoolCount = School::query()->count();

        return view('reports.official.form-thirteen-information', compact('form', 'month', 'period', 'missingMetadata', 'schoolCount'));
    }

    public function savePeriod(Request $request, string $form): RedirectResponse
    {
        if ($form === '12') {
            return to_route('admin.reports.stock.information', ['form' => $form, 'month' => $this->month($request)]);
        }
        $type = $this->formType($form);
        $month = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ])['month'];
        $period = $this->formThirteenPeriod($month);
        $rules = ['month' => ['required', 'date_format:Y-m']];
        foreach (array_keys($this->missingFormThirteenMetadata($period)) as $field) {
            $rules[$field] = ['required', 'string', 'max:255'];
        }
        $data = $request->validate($rules);
        $metadata = [];
        foreach (['district_name', 'upazila_name', 'supplier_name'] as $field) {
            $metadata[$field] = filled($period->{$field}) ? $period->{$field} : ($data[$field] ?? null);
        }
        StockReportPeriod::query()->updateOrCreate(
            ['form_type' => $type, 'month' => $month],
            $metadata,
        );

        return to_route('admin.reports.stock.information', ['form' => $form, 'month' => $month])
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
            if ($report['school_count'] === 0) {
                return to_route('admin.reports.stock.information', ['form' => $form, 'month' => $month, 'school_id' => $schoolId])
                    ->withErrors('No schools are available for this Form 12 report.');
            }
            $period = StockReportPeriod::query()->where('form_type', $type)->where('month', $month)->first()
                ?? new StockReportPeriod(['district_name' => 'চট্টগ্রাম', 'upazila_name' => 'আনোয়ারা']);
            $pages = $this->pages($form, $report, $period);
            $ready = $this->ready($form, $report);
            $missingDeliveryCount = array_sum(array_column($report['schools'], 'missing_delivery_count'));

            return view('reports.official.stock-preview', compact('form', 'month', 'report', 'pages', 'ready', 'schoolId', 'missingDeliveryCount'));
        }

        $period = $this->formThirteenPeriod($month);
        $report = $this->reports->forMonth($month);
        $pages = $this->pages($form, $report, $period);
        $ready = $this->ready($form, $report) && $this->missingFormThirteenMetadata($period) === [];
        $missingDeliveryCount = array_sum(array_column($report['schools'], 'missing_delivery_count'));
        $schoolId = 'all';

        return view('reports.official.stock-preview', compact('form', 'month', 'report', 'pages', 'ready', 'schoolId', 'missingDeliveryCount'));
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
            $period = $this->formThirteenPeriod($month);
        }
        if (! $period || ($form === '12' && $report['school_count'] === 0)) {
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
            if ($form === '13' && $school['items'][$item]['recorded_received'] === null) {
                return false;
            }
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

    private function formThirteenPeriod(string $month): StockReportPeriod
    {
        $periods = StockReportPeriod::query()
            ->where('month', $month)
            ->orderByRaw("CASE WHEN form_type = 'form_13' THEN 0 ELSE 1 END")
            ->get();
        $storedSupplier = $periods->first(fn (StockReportPeriod $period): bool => filled($period->supplier_name))?->supplier_name;
        $supplier = OfficialReportPeriod::query()->where('month', $month)->value('supplier_name');

        return new StockReportPeriod([
            'form_type' => 'form_13',
            'month' => $month,
            'district_name' => $periods->first(fn (StockReportPeriod $period): bool => filled($period->district_name))?->district_name,
            'upazila_name' => $periods->first(fn (StockReportPeriod $period): bool => filled($period->upazila_name))?->upazila_name,
            'supplier_name' => $supplier ?: $storedSupplier,
        ]);
    }

    /** @return array<string, string> */
    private function missingFormThirteenMetadata(StockReportPeriod $period): array
    {
        $missing = [];
        foreach ([
            'district_name' => 'District',
            'upazila_name' => 'Upazila',
            'supplier_name' => 'Supplier/contractor',
        ] as $field => $label) {
            if (blank($period->{$field})) {
                $missing[$field] = $label;
            }
        }

        return $missing;
    }
}
