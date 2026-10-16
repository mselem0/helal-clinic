<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_article');
    }

    public function view(User $user, Article $article): bool
    {
        return $user->can('view_article');
    }

    public function create(User $user): bool
    {
        return $user->can('create_article');
    }

    public function update(User $user, Article $article): bool
    {
        return $user->can('update_article');
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->can('delete_article');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_article');
    }
}
