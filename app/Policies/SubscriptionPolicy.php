<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->isAdmin() || $user->is($subscription->user);
    }

    public function create(User $user): bool
    {
        return $user->isActive() || $user->isAdmin();
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->isAdmin() || $user->is($subscription->user);
    }
}
