<?php

namespace App\Policies;

use App\Models\Preference;
use App\Models\User;

class PreferencePolicy
{
    public function view(User $user, Preference $preference): bool
    {
        return $user->isAdmin() || $user->is($preference->user);
    }

    public function update(User $user, Preference $preference): bool
    {
        return $user->isAdmin() || $user->is($preference->user);
    }
}
