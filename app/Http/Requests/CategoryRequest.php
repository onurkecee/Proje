<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $uniqueName = Rule::unique('categories', 'name')
            ->where('user_id', $this->user()->id)
            ->where('type', $this->input('type'));

        $categoryId = $this->route('category');

        if (is_numeric($categoryId)) {
            $uniqueName->ignore((int) $categoryId);
        }

        return [
            'name' => ['required', 'string', 'max:80', $uniqueName],
            'type' => ['required', Rule::in([CategoryType::Income->value, CategoryType::Expense->value])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Kategori adı zorunludur.',
            'name.max' => 'Kategori adı en fazla 80 karakter olabilir.',
            'name.unique' => 'Bu türde aynı isimli bir kategoriniz zaten var.',
            'type.in' => 'Geçerli bir kategori türü seçin.',
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $categoryId = $this->route('category');

                if (! is_numeric($categoryId) || $validator->errors()->has('type')) {
                    return;
                }

                $category = $this->user()->categories()
                    ->withCount('transactions')
                    ->find((int) $categoryId);

                if (
                    $category !== null
                    && (int) $category->getAttribute('transactions_count') > 0
                    && $category->type->value !== $this->input('type')
                ) {
                    $validator->errors()->add('type', 'Hareketlerde kullanılmış bir kategorinin türü değiştirilemez.');
                }
            },
        ];
    }
}
