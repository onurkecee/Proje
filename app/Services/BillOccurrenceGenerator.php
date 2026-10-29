<?php

namespace App\Services;

use App\Models\Bill;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class BillOccurrenceGenerator
{
    private const MONTHS_AHEAD = 6;

    public function generateFor(Bill $bill): int
    {
        if (! $bill->is_active) {
            return 0;
        }

        $firstDueDate = CarbonImmutable::parse($bill->first_due_on->toDateString());

        if (! $bill->repeat_monthly) {
            return (int) $bill->occurrences()->firstOrCreate(
                ['due_date' => $firstDueDate],
                [
                    'user_id' => $bill->user_id,
                    'amount_minor' => $bill->expected_amount_minor,
                ],
            )->wasRecentlyCreated;
        }

        $month = $firstDueDate->startOfMonth();
        $lastMonth = CarbonImmutable::now()->startOfMonth()->addMonthsNoOverflow(self::MONTHS_AHEAD);
        $created = 0;

        while ($month->lessThanOrEqualTo($lastMonth)) {
            $dueDate = $month->day(min($firstDueDate->day, $month->daysInMonth));

            if ($dueDate->greaterThanOrEqualTo($firstDueDate)
                && ($bill->ends_on === null || $dueDate->lessThanOrEqualTo(CarbonImmutable::parse($bill->ends_on->toDateString())))) {
                $occurrence = $bill->occurrences()->firstOrCreate(
                    ['due_date' => $dueDate],
                    [
                        'user_id' => $bill->user_id,
                        'amount_minor' => $bill->expected_amount_minor,
                    ],
                );

                $created += (int) $occurrence->wasRecentlyCreated;
            }

            $month = $month->addMonthNoOverflow();
        }

        return $created;
    }

    public function generateForActiveBills(): int
    {
        $created = 0;

        Bill::query()
            ->where('is_active', true)
            ->chunkById(100, function (Collection $bills) use (&$created): void {
                foreach ($bills as $bill) {
                    $created += $this->generateFor($bill);
                }
            });

        return $created;
    }
}
