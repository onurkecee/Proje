<?php

namespace App\Services;

use App\Models\Loan;
use Carbon\CarbonImmutable;

class LoanInstallmentGenerator
{
    public function generateFor(Loan $loan): void
    {
        $firstDueDate = CarbonImmutable::parse($loan->first_due_on->toDateString());

        for ($number = 1; $number <= $loan->installment_count; $number++) {
            $month = $firstDueDate->startOfMonth()->addMonthsNoOverflow($number - 1);
            $dueDate = $month->day(min($firstDueDate->day, $month->daysInMonth));
            $amountMinor = $number === $loan->installment_count
                ? $loan->total_repayment_minor - ($loan->installment_amount_minor * ($number - 1))
                : $loan->installment_amount_minor;

            $loan->installments()->firstOrCreate(
                ['installment_number' => $number],
                [
                    'user_id' => $loan->user_id,
                    'due_on' => $dueDate,
                    'amount_minor' => $amountMinor,
                ],
            );
        }
    }
}
