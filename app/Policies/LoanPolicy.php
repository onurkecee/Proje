<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id
            && ! $loan->installments()->where('paid_amount_minor', '>', 0)->exists();
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $this->update($user, $loan);
    }
}
