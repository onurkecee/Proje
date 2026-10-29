<?php

namespace App\Policies;

use App\Models\CreditCardStatement;
use App\Models\User;

class CreditCardStatementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CreditCardStatement $statement): bool
    {
        return $user->id === $statement->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CreditCardStatement $statement): bool
    {
        return $user->id === $statement->user_id && $statement->paid_amount_minor === 0;
    }

    public function delete(User $user, CreditCardStatement $statement): bool
    {
        return $user->id === $statement->user_id && $statement->paid_amount_minor === 0;
    }
}
