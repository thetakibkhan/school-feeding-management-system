<?php

namespace App\Services;

use App\Models\OfficialReportPeriod;
use Illuminate\Support\Facades\DB;

class FormTenInvoiceNumberGenerator
{
    private const SEQUENCE_ID = 1;

    public function ensureForMonth(string $month): OfficialReportPeriod
    {
        return DB::transaction(function () use ($month): OfficialReportPeriod {
            $period = OfficialReportPeriod::query()->firstOrCreate(['month' => $month]);
            $period = OfficialReportPeriod::query()
                ->whereKey($period->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $sequence = DB::table('form_ten_invoice_sequences')
                ->where('id', self::SEQUENCE_ID)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                throw new \LogicException('The Form 10 invoice-number sequence has not been initialized.');
            }

            $storedNumber = (string) ($period->invoice_number ?? '');
            $lastNumber = max((int) $sequence->last_number, $this->highestExistingNumber());
            if ($period->invoice_number_generated_at !== null
                && preg_match('/\AAN-(\d{5,})\z/', $storedNumber, $matches) === 1) {
                $lastNumber = max($lastNumber, (int) $matches[1]);
                $this->saveSequence($lastNumber);

                return $period;
            }

            do {
                $lastNumber++;
                $invoiceNumber = 'AN-'.str_pad((string) $lastNumber, 5, '0', STR_PAD_LEFT);
            } while (OfficialReportPeriod::query()
                ->whereNotNull('invoice_number_generated_at')
                ->where('invoice_number', $invoiceNumber)
                ->exists());

            $this->saveSequence($lastNumber);
            $period->invoice_number = $invoiceNumber;
            $period->invoice_number_generated_at = now();
            $period->save();

            return $period;
        });
    }

    private function highestExistingNumber(): int
    {
        return OfficialReportPeriod::query()
            ->where('invoice_number', 'like', 'AN-%')
            ->whereNotNull('invoice_number_generated_at')
            ->pluck('invoice_number')
            ->reduce(static function (int $highest, string $invoiceNumber): int {
                if (preg_match('/\AAN-(\d{5,})\z/', $invoiceNumber, $matches) !== 1) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);
    }

    private function saveSequence(int $lastNumber): void
    {
        DB::table('form_ten_invoice_sequences')
            ->where('id', self::SEQUENCE_ID)
            ->update(['last_number' => $lastNumber]);
    }
}
