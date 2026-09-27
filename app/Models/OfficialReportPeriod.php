<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'month',
    'supplier_name',
    'invoice_date',
    'contract_number',
    'bank_account_name',
    'bank_account_number',
    'bank_name',
    'bank_branch',
    'bank_routing_number',
    'related_service_unit_price',
])]
class OfficialReportPeriod extends Model
{
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'invoice_number_generated_at' => 'datetime',
        ];
    }
}
