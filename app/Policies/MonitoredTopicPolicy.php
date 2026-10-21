<?php

namespace App\Policies;

use App\Models\MonitoredTopic;
use App\Models\User;

class MonitoredTopicPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MonitoredTopic $topic): bool
    {
        return $user->isAdmin() || $user->is($topic->user);
    }

    public function create(User $user): bool
    {
        return $user->isActive() || $user->isAdmin();
    }

    public function update(User $user, MonitoredTopic $topic): bool
    {
        return $user->isAdmin() || $user->is($topic->user);
    }

    public function delete(User $user, MonitoredTopic $topic): bool
    {
        return $user->isAdmin() || $user->is($topic->user);
    }

    public function pause(User $user, MonitoredTopic $topic): bool
    {
        return $this->update($user, $topic);
    }

    public function resume(User $user, MonitoredTopic $topic): bool
    {
        return $this->update($user, $topic);
    }

    public function run(User $user, MonitoredTopic $topic): bool
    {
        return $this->update($user, $topic);
    }
}
