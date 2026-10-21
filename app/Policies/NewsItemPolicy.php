<?php

namespace App\Policies;

use App\Models\NewsItem;
use App\Models\User;

class NewsItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, NewsItem $newsItem): bool
    {
        return $user->isAdmin() || $user->is($newsItem->user);
    }

    public function create(User $user): bool
    {
        return $user->isActive() || $user->isAdmin();
    }

    public function update(User $user, NewsItem $newsItem): bool
    {
        return $user->isAdmin() || $user->is($newsItem->user);
    }

    public function delete(User $user, NewsItem $newsItem): bool
    {
        return $user->isAdmin() || $user->is($newsItem->user);
    }
}
