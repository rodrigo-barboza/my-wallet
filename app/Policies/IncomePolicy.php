<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Income;
use App\Models\User;

class IncomePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Income $income): bool
    {
        return $income->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Income $income): bool
    {
        return $income->user_id === $user->id;
    }

    public function delete(User $user, Income $income): bool
    {
        return $income->user_id === $user->id;
    }

    public function attach(User $user, Income $income): bool
    {
        return $income->user_id === $user->id;
    }

    public function detach(User $user, Income $income): bool
    {
        return $income->user_id === $user->id;
    }
}
