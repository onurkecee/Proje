<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreditCardStatementRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('user_id', $this->user()->id)
                    ->where('type', AccountType::CreditCard->value)
                    ->where('is_active', true),
            ],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'due_on' => ['required', 'date', 'after_or_equal:period_end'],
            'amount' => ['required', 'string', 'regex:/^\d{1,12}(?:[,.]\d{1,2})?$/'],
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
                $accountId = $this->input('account_id');
                $periodStart = $this->input('period_start');
                $statementId = $this->route('credit_card_statement');

                if (is_numeric($accountId) && is_string($periodStart)) {
                    $duplicate = $this->user()->creditCardStatements()
                        ->where('account_id', (int) $accountId)
                        ->whereDate('period_start', $periodStart)
                        ->when(is_numeric($statementId), fn ($query) => $query->where('id', '!=', (int) $statementId))
                        ->exists();

                    if ($duplicate) {
                        $validator->errors()->add('period_start', 'Bu kart için bu başlangıç tarihli ekstre zaten kayıtlı.');
                    }
                }

                if (is_string($amount) && preg_match('/^\d{1,12}(?:[,.]\d{1,2})?$/', $amount) && (int) str_replace([',', '.'], '', $amount) === 0) {
                    $validator->errors()->add('amount', 'Ekstre borcu sıfırdan büyük olmalıdır.');
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
            'account_id.required' => 'Bir kredi kartı seçin.',
            'period_start.unique' => 'Bu kart için bu başlangıç tarihli ekstre zaten kayıtlı.',
            'period_end.after_or_equal' => 'Ekstre bitişi başlangıç tarihinden önce olamaz.',
            'due_on.after_or_equal' => 'Son ödeme tarihi ekstre bitişinden önce olamaz.',
            'amount.regex' => 'Tutarı en fazla iki kuruş hanesiyle girin.',
        ];
    }
}
