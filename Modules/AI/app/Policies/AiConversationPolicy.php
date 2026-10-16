<?php

namespace Modules\AI\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AI\Models\AiConversation;

class AiConversationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_ai::conversation');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AiConversation $aiConversation): bool
    {
        return $user->can('view_ai::conversation') && $user->id === $aiConversation->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_ai::conversation');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AiConversation $aiConversation): bool
    {
        return $user->can('update_ai::conversation') && $user->id === $aiConversation->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AiConversation $aiConversation): bool
    {
        return $user->can('delete_ai::conversation') && $user->id === $aiConversation->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AiConversation $aiConversation): bool
    {
        return $user->can('restore_ai::conversation') && $user->id === $aiConversation->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AiConversation $aiConversation): bool
    {
        return $user->can('force_delete_ai::conversation') && $user->id === $aiConversation->user_id;
    }
}
