<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreditCardPaymentRequest extends FormRequest
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
                    ->whereIn('type', [AccountType::Bank->value, AccountType::Cash->value])
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
                $statementId = $this->route('credit_card_statement');
                $accountId = $this->input('account_id');

                if (is_numeric($statementId) && is_numeric($accountId)) {
                    $statement = $this->user()->creditCardStatements()
                        ->with('account:id,currency')
                        ->find((int) $statementId);
                    $account = $this->user()->accounts()->find((int) $accountId);

                    if ($statement !== null
                        && $account !== null
                        && $statement->account->currency !== $account->currency) {
                        $validator->errors()->add('account_id', 'Ödeme hesabı ve kredi kartının para birimi aynı olmalıdır.');
                    }
                }

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
            'account_id.required' => 'Ödemenin yapılacağı banka veya nakit hesabı seçin.',
            'amount.regex' => 'Tutarı en fazla iki kuruş hanesiyle girin.',
            'paid_at.before_or_equal' => 'Ödeme tarihi bugünden ileri olamaz.',
        ];
    }
}
