<?php

namespace App\Services;

readonly class DemandCalculation
{
    public function __construct(
        public int $quantity,
        public ?int $studentCount,
        public ?int $rationGrams,
    ) {}
}
