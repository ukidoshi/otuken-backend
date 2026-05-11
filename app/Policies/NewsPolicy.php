<?php

namespace App\Policies;

use App\Models\News;
use App\Models\User;

class NewsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('news.read');
    }

    public function view(User $user, News $news): bool
    {
        return $user->can('news.read');
    }

    public function create(User $user): bool
    {
        return $user->can('news.create');
    }

    public function update(User $user, News $news): bool
    {
        return $user->can('news.update');
    }

    public function delete(User $user, News $news): bool
    {
        return $user->can('news.delete');
    }

    public function restore(User $user, News $news): bool
    {
        return $user->can('news.delete');
    }

    public function forceDelete(User $user, News $news): bool
    {
        return $user->can('news.delete');
    }

    public function publish(User $user, News $news): bool
    {
        return $user->can('news.publish');
    }

    public function unpublish(User $user, News $news): bool
    {
        return $user->can('news.unpublish');
    }

    public function archive(User $user, News $news): bool
    {
        return $user->can('news.archive');
    }

    public function approve(User $user, News $news): bool
    {
        return $user->can('news.approve');
    }

    public function preview(User $user, News $news): bool
    {
        return $user->can('news.preview');
    }
}
