<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\SchoolMonthlyPlanningQuantity;
use Illuminate\Database\Seeder;
use LogicException;

class SeptemberSchoolPlanningQuantitySeeder extends Seeder
{
    /**
     * Daily school quantities transcribed from the supplied September 2026
     * school list. For assessment reports, each scheduled quantity is treated
     * as a fixture delivery under the explicit delivered = demand assumption.
     * These values never create Field Staff delivery or chalan records.
     *
     * @var list<int>
     */
    private const DAILY_SOURCE_QUANTITIES = [
        172, 293, 271, 247, 411, 304, 571, 319, 229, 471,
        253, 101, 303, 196, 143, 236, 175, 248, 85, 231,
        127, 98, 124, 440, 170, 149, 104, 113, 226, 100,
        370, 225, 280, 86, 178, 334, 343, 210, 270, 162,
        175, 386, 75, 205, 113, 199, 193, 240, 184, 144,
        177, 307, 315, 177, 114, 100, 446, 213, 158, 129,
        156, 163, 57, 171, 123, 74, 118, 84, 49, 89,
        73, 205, 144, 214, 109, 188, 119, 201, 90, 53,
        139, 291, 58, 188, 140, 142, 122, 166, 184, 178,
        124, 158, 53, 60, 47, 170, 207, 149, 93, 143,
        213, 119, 62, 155, 56, 104, 153, 132, 87, 63,
    ];

    private const SOURCE_TOTALS = [
        'bun_quantity' => 317664,
        'egg_quantity' => 238248,
        'banana_quantity' => 99270,
    ];

    private const SOURCE_DOCUMENT = 'GPSFP_School List_Anwara Upazila.pdf';

    public function run(): void
    {
        if (count(self::DAILY_SOURCE_QUANTITIES) !== 110) {
            throw new LogicException('The September school demand source must contain exactly 110 school rows.');
        }

        $days = DemandSetupSeeder::SOURCE_DISTRIBUTION_DAY_TOTALS;
        $expectedTotals = [
            'bun_quantity' => array_sum(self::DAILY_SOURCE_QUANTITIES) * $days['bun'],
            'egg_quantity' => array_sum(self::DAILY_SOURCE_QUANTITIES) * $days['boiled_egg'],
            'banana_quantity' => array_sum(self::DAILY_SOURCE_QUANTITIES) * $days['banana'],
        ];

        if ($expectedTotals !== self::SOURCE_TOTALS) {
            throw new LogicException('September school demand quantities do not match the source PDF totals.');
        }

        $schoolCodes = array_map(
            static fn (int $index): string => sprintf('AN-%03d', $index),
            range(1, 110),
        );
        $schools = School::query()->whereIn('school_code', $schoolCodes)->get()->keyBy('school_code');
        $missingSchoolCodes = array_values(array_diff($schoolCodes, $schools->keys()->all()));

        if ($missingSchoolCodes !== []) {
            throw new LogicException('September source quantities require the 110 seeded Anwara schools.');
        }

        foreach (self::DAILY_SOURCE_QUANTITIES as $index => $dailyQuantity) {
            $schoolCode = $schoolCodes[$index];
            $school = $schools->get($schoolCode);

            SchoolMonthlyPlanningQuantity::query()->updateOrCreate(
                ['school_id' => $school->id, 'month' => '2026-09'],
                [
                    'bun_quantity' => $dailyQuantity * $days['bun'],
                    'egg_quantity' => $dailyQuantity * $days['boiled_egg'],
                    'banana_quantity' => $dailyQuantity * $days['banana'],
                    'source_document' => self::SOURCE_DOCUMENT,
                ],
            );
        }
    }
}
