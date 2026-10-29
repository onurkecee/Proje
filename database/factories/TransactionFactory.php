<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => fn (array $attributes) => Account::factory()
                ->create(['user_id' => $attributes['user_id']])
                ->getKey(),
            'category_id' => fn (array $attributes) => Category::factory()
                ->create([
                    'user_id' => $attributes['user_id'],
                    'type' => CategoryType::Expense,
                ])
                ->getKey(),
            'type' => TransactionType::Expense,
            'amount_minor' => fake()->numberBetween(1, 500_000),
            'description' => fake()->sentence(),
            'occurred_at' => now()->toDateString(),
        ];
    }
}
