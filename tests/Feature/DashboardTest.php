<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_reports_monthly_totals_trends_categories_accounts_and_upcoming_debts(): void
    {
        Carbon::setTestNow('2026-10-25 12:00:00');
        CarbonImmutable::setTestNow('2026-10-25 12:00:00');
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $bank = Account::factory()->for($user)->create([
            'name' => 'Banka',
            'opening_balance_minor' => 20_000,
        ]);
        $card = Account::factory()->for($user)->create([
            'name' => 'Kart',
            'type' => 'credit_card',
            'opening_balance_minor' => 0,
        ]);
        $expenseCategory = Category::factory()->for($user)->create([
            'name' => 'Günlük harcamalar',
            'type' => 'expense',
        ]);
        $incomeCategory = Category::factory()->for($user)->create([
            'name' => 'Maaş',
            'type' => 'income',
        ]);

        $user->transactions()->create([
            'account_id' => $bank->id,
            'category_id' => $incomeCategory->id,
            'type' => 'income',
            'amount_minor' => 10_000,
            'occurred_at' => '2026-10-05',
        ]);
        $user->transactions()->create([
            'account_id' => $bank->id,
            'category_id' => $expenseCategory->id,
            'type' => 'expense',
            'amount_minor' => 3_000,
            'occurred_at' => '2026-10-06',
        ]);
        $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $expenseCategory->id,
            'type' => 'expense',
            'amount_minor' => 5_000,
            'occurred_at' => '2026-10-07',
        ]);
        $user->transactions()->create([
            'account_id' => $bank->id,
            'destination_account_id' => $card->id,
            'type' => 'transfer',
            'amount_minor' => 5_000,
            'occurred_at' => '2026-10-08',
        ]);
        $user->transactions()->create([
            'account_id' => $bank->id,
            'category_id' => $expenseCategory->id,
            'type' => 'expense',
            'amount_minor' => 2_000,
            'description' => 'Kredi taksiti',
            'occurred_at' => '2026-10-09',
        ]);
        $user->transactions()->create([
            'account_id' => $bank->id,
            'category_id' => $incomeCategory->id,
            'type' => 'income',
            'amount_minor' => 8_000,
            'occurred_at' => '2026-09-10',
        ]);
        $user->transactions()->create([
            'account_id' => $bank->id,
            'category_id' => $expenseCategory->id,
            'type' => 'expense',
            'amount_minor' => 1_000,
            'occurred_at' => '2026-09-11',
        ]);
        $otherAccount = Account::factory()->for($otherUser)->create();
        $otherIncomeCategory = Category::factory()->for($otherUser)->create(['type' => 'income']);
        $otherUser->transactions()->create([
            'account_id' => $otherAccount->id,
            'category_id' => $otherIncomeCategory->id,
            'type' => 'income',
            'amount_minor' => 999_999,
            'occurred_at' => '2026-10-10',
        ]);

        $bill = $user->bills()->create([
            'name' => 'Elektrik',
            'type' => 'electricity',
            'expected_amount_minor' => 1_000,
            'first_due_on' => '2026-10-27',
            'repeat_monthly' => false,
            'is_active' => true,
        ]);
        $billOccurrence = $bill->occurrences()->create([
            'user_id' => $user->id,
            'due_date' => '2026-10-27',
            'amount_minor' => 1_000,
        ]);
        $user->creditCardStatements()->create([
            'account_id' => $card->id,
            'period_start' => '2026-09-25',
            'period_end' => '2026-10-24',
            'due_on' => '2026-10-28',
            'amount_minor' => 2_000,
            'paid_amount_minor' => 500,
        ]);
        $loan = $user->loans()->create([
            'name' => 'İhtiyaç kredisi',
            'lender' => null,
            'total_repayment_minor' => 10_000,
            'installment_amount_minor' => 3_000,
            'installment_count' => 4,
            'first_due_on' => '2026-10-29',
            'is_active' => true,
        ]);
        $loan->installments()->create([
            'user_id' => $user->id,
            'installment_number' => 1,
            'due_on' => '2026-10-29',
            'amount_minor' => 3_000,
            'paid_amount_minor' => 1_000,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', ['month' => '2026-10']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('month', '2026-10')
                ->where('previousMonth', '2026-09')
                ->where('nextMonth', '2026-11')
                ->where('canGoNext', false)
                ->where('summary.incomeMinor', '10000')
                ->where('summary.expenseMinor', '10000')
                ->where('summary.netMinor', '0')
                ->where('summary.previousIncomeMinor', '8000')
                ->where('summary.previousExpenseMinor', '1000')
                ->where('summary.netWorthMinor', '27000')
                ->where('summary.transactionCount', 4)
                ->where('categoryTotalMinor', '10000')
                ->where('categories.0.name', 'Günlük harcamalar')
                ->where('categories.0.amountMinor', '10000')
                ->where('trend.5.incomeMinor', '10000')
                ->where('trend.5.expenseMinor', '10000')
                ->has('trend', 6)
                ->has('upcomingPayments', 3)
                ->where('upcomingPayments.0.id', 'bill-'.$billOccurrence->id)
                ->where('upcomingPayments.0.amountMinor', '1000')
                ->where('upcomingPayments.1.amountMinor', '1500')
                ->where('upcomingPayments.2.amountMinor', '2000')
                ->has('recentTransactions', 7),
            );
    }

    public function test_dashboard_defaults_to_current_month_and_handles_empty_data(): void
    {
        Carbon::setTestNow('2026-10-25 12:00:00');
        CarbonImmutable::setTestNow('2026-10-25 12:00:00');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('month', '2026-10')
                ->where('summary.incomeMinor', '0')
                ->where('summary.expenseMinor', '0')
                ->where('summary.netWorthMinor', '0')
                ->where('summary.accountCount', 0)
                ->where('summary.transactionCount', 0)
                ->where('categories', [])
                ->where('upcomingPayments', [])
                ->has('trend', 6)
                ->has('recentTransactions', 0),
            );
    }

    public function test_dashboard_rejects_invalid_month_selection(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->get(route('dashboard', ['month' => '2026-13']))
            ->assertSessionHasErrors('month');
    }
}
