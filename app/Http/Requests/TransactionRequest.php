<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TransactionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->input('type');
        $userId = $this->user()->id;

        return [
            'type' => ['required', Rule::in(array_column(TransactionType::cases(), 'value'))],
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where('user_id', $userId)],
            'destination_account_id' => [
                'nullable',
                'integer',
                'required_if:type,transfer',
                'prohibited_unless:type,transfer',
                Rule::exists('accounts', 'id')->where('user_id', $userId),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(in_array($type, [TransactionType::Income->value, TransactionType::Expense->value], true)),
                'prohibited_if:type,transfer',
                Rule::exists('categories', 'id')
                    ->where('user_id', $userId)
                    ->where('type', $type),
            ],
            'amount' => ['required', 'string', 'regex:/^\d{1,12}(?:[,.]\d{1,2})?$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'occurred_at' => ['required', 'date'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $amount = $this->input('amount');

                if (is_string($amount) && preg_match('/^\d{1,12}(?:[,.]\d{1,2})?$/', $amount) && (int) str_replace([',', '.'], '', $amount) === 0) {
                    $validator->errors()->add('amount', 'Tutar sıfırdan büyük olmalıdır.');
                }

                if ($this->input('type') !== TransactionType::Transfer->value) {
                    return;
                }

                $sourceId = $this->input('account_id');
                $destinationId = $this->input('destination_account_id');

                if (! is_numeric($sourceId) || ! is_numeric($destinationId)) {
                    return;
                }

                if ((int) $sourceId === (int) $destinationId) {
                    $validator->errors()->add('destination_account_id', 'Transfer için farklı bir hedef hesap seçin.');

                    return;
                }

                $accounts = $this->user()->accounts()
                    ->whereIn('id', [(int) $sourceId, (int) $destinationId])
                    ->get(['id', 'currency']);

                if ($accounts->count() === 2 && $accounts->pluck('currency')->unique()->count() > 1) {
                    $validator->errors()->add('destination_account_id', 'Transfer hesaplarının para birimi aynı olmalıdır.');
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
            'account_id.required' => 'Bir hesap seçin.',
            'category_id.required' => 'Bir kategori seçin.',
            'destination_account_id.required' => 'Transfer hedefini seçin.',
            'amount.regex' => 'Tutarı en fazla iki kuruş hanesiyle girin.',
            'occurred_at.required' => 'İşlem tarihi zorunludur.',
        ];
    }
}
