<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IncomeGroup;
use App\Models\User;

class IncomeGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, IncomeGroup $group): bool
    {
        return $group->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, IncomeGroup $group): bool
    {
        return $group->user_id === $user->id;
    }

    public function delete(User $user, IncomeGroup $group): bool
    {
        return $group->user_id === $user->id;
    }
}
