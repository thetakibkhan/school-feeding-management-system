<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SchoolPrincipalContactSeeder extends Seeder
{
    /**
     * @return array<string, array{name: string, principal_name: string, principal_mobile: string}>
     */
    private function contactsBySchoolCode(): array
    {
        $path = database_path('seeders/data/school-name-principal-name-mobile-no.json');
        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException('School principal contact seed data could not be read.');
        }

        try {
            $records = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException('School principal contact seed data is invalid JSON.', previous: $exception);
        }

        if (! is_array($records) || count($records) !== 110) {
            throw new RuntimeException('School principal contact seed data must contain 110 records.');
        }

        $contacts = [];

        foreach ($records as $index => $record) {
            $expectedId = $index + 1;
            if (
                ! is_array($record)
                || ($record['id'] ?? null) !== $expectedId
                || ! is_string($record['school_name'] ?? null)
                || trim($record['school_name']) === ''
                || ! is_string($record['principal_name'] ?? null)
                || ! is_string($record['principal_phone'] ?? null)
                || trim($record['principal_name']) === ''
                || trim($record['principal_phone']) === ''
            ) {
                throw new RuntimeException("School principal contact record {$expectedId} is incomplete or out of order.");
            }

            // JSON ids follow the supplied school-list order: id 1 is AN-001.
            $contacts[sprintf('AN-%03d', $expectedId)] = [
                'name' => trim($record['school_name']),
                'principal_name' => trim($record['principal_name']),
                'principal_mobile' => trim($record['principal_phone']),
            ];
        }

        return $contacts;
    }

    public function run(): void
    {
        $contacts = $this->contactsBySchoolCode();

        DB::transaction(function () use ($contacts): void {
            $schools = School::query()
                ->whereIn('school_code', array_keys($contacts))
                ->get()
                ->keyBy('school_code');

            if ($schools->count() !== count($contacts)) {
                throw new RuntimeException('All 110 seeded schools must exist before principal contacts are seeded.');
            }

            foreach ($contacts as $schoolCode => $contact) {
                $school = $schools->get($schoolCode);
                $school->name = $contact['name'];
                $school->principal_name = $contact['principal_name'];
                $school->principal_mobile = $contact['principal_mobile'];
                $school->save();
            }
        });
    }
}
