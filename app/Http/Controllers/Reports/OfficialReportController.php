<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\OfficialReportPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficialReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['sometimes', 'required', 'date_format:Y-m'],
        ]);

        return view('reports.official.index', [
            'month' => $month = $validated['month'] ?? '2026-09',
        ]);
    }

    public function savePeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['nullable', 'date'],
            'contract_number' => ['nullable', 'string', 'max:150'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_routing_number' => ['nullable', 'string', 'max:100'],
        ]);

        $month = $validated['month'];
        unset($validated['month']);
        OfficialReportPeriod::query()->updateOrCreate(['month' => $month], $validated);

        return to_route('admin.reports.form-ten', ['month' => $month])
            ->with('status', 'Report-period details saved.');
    }
}
