<?php

namespace App\Http\Requests;

use App\Enums\BillType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'repeat_monthly' => $this->boolean('repeat_monthly'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_column(BillType::cases(), 'value'))],
            'expected_amount' => ['required', 'string', 'regex:/^\d{1,12}(?:[,.]\d{1,2})?$/'],
            'first_due_on' => ['required', 'date'],
            'repeat_monthly' => ['required', 'boolean'],
            'ends_on' => [
                'nullable',
                'date',
                'after_or_equal:first_due_on',
                'prohibited_unless:repeat_monthly,1',
            ],
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
                $amount = $this->input('expected_amount');

                if (is_string($amount) && preg_match('/^\d{1,12}(?:[,.]\d{1,2})?$/', $amount) && (int) str_replace([',', '.'], '', $amount) === 0) {
                    $validator->errors()->add('expected_amount', 'Beklenen tutar sıfırdan büyük olmalıdır.');
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
            'name.required' => 'Fatura adı zorunludur.',
            'type.required' => 'Fatura türü seçin.',
            'expected_amount.regex' => 'Tutarı en fazla iki kuruş hanesiyle girin.',
            'first_due_on.required' => 'İlk vade tarihi zorunludur.',
            'ends_on.prohibited_unless' => 'Bitiş tarihi yalnızca tekrarlayan faturalar için kullanılabilir.',
        ];
    }
}
