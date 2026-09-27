<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['form_type', 'month', 'district_name', 'upazila_name', 'supplier_name'])]
class StockReportPeriod extends Model
{
}
