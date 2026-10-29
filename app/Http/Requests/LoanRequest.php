<?php

namespace App\Http\Requests;

use App\Support\MoneyAmount;
use Illuminate\Foundation\Http\FormRequest;

class LoanRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'lender' => ['nullable', 'string', 'max:120'],
            'total_repayment' => ['required', 'string', 'regex:/^\d{1,12}(?:[,.]\d{1,2})?$/'],
            'installment_amount' => ['required', 'string', 'regex:/^\d{1,12}(?:[,.]\d{1,2})?$/'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:600'],
            'first_due_on' => ['required', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $total = $this->input('total_repayment');
                $installment = $this->input('installment_amount');
                $count = $this->input('installment_count');

                foreach ([
                    'total_repayment' => $total,
                    'installment_amount' => $installment,
                ] as $field => $amount) {
                    if (is_string($amount)
                        && preg_match('/^\d{1,12}(?:[,.]\d{1,2})?$/', $amount)
                        && MoneyAmount::toMinor($amount) <= 0) {
                        $validator->errors()->add($field, 'Tutar sıfırdan büyük olmalıdır.');
                    }
                }

                if ($validator->errors()->hasAny(['total_repayment', 'installment_amount', 'installment_count'])
                    || ! is_string($total)
                    || ! is_string($installment)
                    || ! is_numeric($count)) {
                    return;
                }

                $totalMinor = MoneyAmount::toMinor($total);
                $installmentMinor = MoneyAmount::toMinor($installment);
                $installmentCount = (int) $count;

                if ($installmentCount === 1 && $installmentMinor !== $totalMinor) {
                    $validator->errors()->add('installment_amount', 'Tek taksit tutarı toplam geri ödeme tutarıyla aynı olmalıdır.');

                    return;
                }

                if ($installmentMinor >= $totalMinor
                    || $installmentMinor * ($installmentCount - 1) >= $totalMinor) {
                    $validator->errors()->add('installment_amount', 'Taksit tutarı ve sayısı toplam geri ödeme tutarını aşmamalıdır.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Kredi adı zorunludur.',
            'total_repayment.regex' => 'Toplam tutarı en fazla iki kuruş hanesiyle girin.',
            'installment_amount.regex' => 'Taksit tutarını en fazla iki kuruş hanesiyle girin.',
            'installment_count.max' => 'Taksit sayısı en fazla 600 olabilir.',
            'first_due_on.required' => 'İlk taksit tarihi zorunludur.',
        ];
    }
}
