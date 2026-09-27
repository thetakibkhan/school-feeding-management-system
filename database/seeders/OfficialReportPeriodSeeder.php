<?php

namespace Database\Seeders;

use App\Models\OfficialReportPeriod;
use Illuminate\Database\Seeder;

class OfficialReportPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $period = OfficialReportPeriod::query()->firstOrCreate(
            ['month' => '2026-09'],
            [
                'supplier_name' => 'স্বদেশ পল্লী লিমিটেড',
                'related_service_unit_price' => '0.000',
            ],
        );

        $period->forceFill([
            'supplier_name' => $period->supplier_name ?: 'স্বদেশ পল্লী লিমিটেড',
            'related_service_unit_price' => $period->related_service_unit_price ?? '0.000',
        ])->save();
    }
}
