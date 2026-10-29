<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AccountRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(array_column(AccountType::cases(), 'value'))],
            'opening_balance' => ['required', 'string', 'regex:/^-?\d{1,12}(?:[,.]\d{1,2})?$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $accountId = $this->route('account');

                if (! is_numeric($accountId)) {
                    return;
                }

                $account = $this->user()->accounts()->find((int) $accountId);

                if ($account === null || $this->input('type') === $account->type->value) {
                    return;
                }

                if ($account->transactions()->exists()
                    || $account->incomingTransfers()->exists()
                    || $account->creditCardStatements()->exists()) {
                    $validator->errors()->add('type', 'Hareket veya ekstre kaydı bulunan hesabın türü değiştirilemez.');
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
            'name.required' => 'Hesap adı zorunludur.',
            'name.max' => 'Hesap adı en fazla 80 karakter olabilir.',
            'type.in' => 'Geçerli bir hesap türü seçin.',
            'opening_balance.regex' => 'Açılış bakiyesini en fazla iki kuruş hanesiyle girin.',
        ];
    }
}
