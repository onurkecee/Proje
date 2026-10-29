<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoanPaymentRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('user_id', $userId)
                    ->where('is_active', true)
                    ->where('currency', 'TRY'),
            ],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('user_id', $userId)
                    ->where('type', CategoryType::Expense->value)
                    ->where('is_active', true),
            ],
            'amount' => ['required', 'string', 'regex:/^\d{1,12}(?:[,.]\d{1,2})?$/'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $amount = $this->input('amount');

                if (is_string($amount) && preg_match('/^\d{1,12}(?:[,.]\d{1,2})?$/', $amount) && (int) str_replace([',', '.'], '', $amount) === 0) {
                    $validator->errors()->add('amount', 'Ödeme tutarı sıfırdan büyük olmalıdır.');
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
            'account_id.required' => 'Ödemenin yapılacağı hesabı seçin.',
            'category_id.required' => 'Bir gider kategorisi seçin.',
            'amount.regex' => 'Tutarı en fazla iki kuruş hanesiyle girin.',
            'paid_at.before_or_equal' => 'Ödeme tarihi bugünden ileri olamaz.',
        ];
    }
}
