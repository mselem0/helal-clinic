<?php

namespace Modules\AI\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AI\Models\AiMessage;

class AiMessagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_ai::message');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AiMessage $aiMessage): bool
    {
        return $user->can('view_ai::message') && $user->id === $aiMessage->conversation->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_ai::message');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AiMessage $aiMessage): bool
    {
        return $user->can('update_ai::message') && $user->id === $aiMessage->conversation->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AiMessage $aiMessage): bool
    {
        return $user->can('delete_ai::message') && $user->id === $aiMessage->conversation->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AiMessage $aiMessage): bool
    {
        return $user->can('restore_ai::message') && $user->id === $aiMessage->conversation->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AiMessage $aiMessage): bool
    {
        return $user->can('force_delete_ai::message') && $user->id === $aiMessage->conversation->user_id;
    }
}
