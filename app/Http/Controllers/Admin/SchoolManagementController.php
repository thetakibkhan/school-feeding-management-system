<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\SchoolDeletionBlocked;
use App\Services\SchoolManagementService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\View\View;

class SchoolManagementController extends Controller
{
    public function __construct(private readonly SchoolManagementService $schools) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        return view('admin.schools.index', [
            'schools' => $this->schools->listSchools($search ?: null),
            'search' => $search,
        ]);
    }

    public function show(School $school): View
    {
        return view('admin.schools.show', [
            'school' => $school->load(['studentCounts' => fn ($query) => $query->orderByDesc('effective_start_date')]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->schoolCreationRules());

        $this->schools->createSchool(
            $data['school_code'],
            $data['emis_code'],
            $data['name'],
            $data['student_count'],
            $data['effective_start_date'],
            $data['principal_name'] ?? null,
            $data['principal_mobile'] ?? null,
        );

        return to_route('admin.schools.index')->with('status', 'School created.');
    }

    public function update(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate([
            'school_code' => ['required', 'string', 'max:100', Rule::unique('schools', 'school_code')->ignore($school)],
            'emis_code' => ['required', 'string', 'max:100', Rule::unique('schools', 'emis_code')->ignore($school)],
            'name' => ['required', 'string', 'max:255'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'principal_mobile' => ['nullable', 'string', 'max:32'],
        ]);

        $this->schools->updateSchool(
            $school,
            $data['school_code'],
            $data['emis_code'],
            $data['name'],
            $data['principal_name'] ?? null,
            $data['principal_mobile'] ?? null,
        );

        return to_route('admin.schools.show', $school)->with('status', 'School updated.');
    }

    public function storeStudentCount(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate([
            'student_count' => ['required', 'integer', 'min:1'],
            'effective_start_date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, Closure $fail) use ($school): void {
                    $exists = $school->studentCounts()
                        ->whereDate('effective_start_date', $value)
                        ->exists();

                    if ($exists) {
                        $fail('A student count already exists for this effective start date.');
                    }
                },
            ],
        ]);

        $this->schools->addStudentCount($school, $data['student_count'], $data['effective_start_date']);

        return to_route('admin.schools.show', $school)->with('status', 'Student count added.');
    }

    public function destroy(School $school): RedirectResponse
    {
        try {
            $this->schools->deleteSchool($school);
        } catch (SchoolDeletionBlocked $exception) {
            return to_route('admin.schools.index')->with('error', $exception->getMessage());
        }

        return to_route('admin.schools.index')->with('status', 'School deleted.');
    }

    /**
     * @return array<string, array<int, string|Unique>>
     */
    private function schoolCreationRules(): array
    {
        return [
            'school_code' => ['required', 'string', 'max:100', 'unique:schools,school_code'],
            'emis_code' => ['required', 'string', 'max:100', 'unique:schools,emis_code'],
            'name' => ['required', 'string', 'max:255'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'principal_mobile' => ['nullable', 'string', 'max:32'],
            'student_count' => ['required', 'integer', 'min:1'],
            'effective_start_date' => ['required', 'date'],
        ];
    }
}
