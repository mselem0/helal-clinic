<?php

namespace Modules\AI\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AI\Models\KnowledgeChunk;

class KnowledgeChunkPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_knowledge::chunk');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, KnowledgeChunk $knowledgeChunk): bool
    {
        return $user->can('view_knowledge::chunk');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_knowledge::chunk');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, KnowledgeChunk $knowledgeChunk): bool
    {
        return $user->can('update_knowledge::chunk');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, KnowledgeChunk $knowledgeChunk): bool
    {
        return $user->can('delete_knowledge::chunk');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, KnowledgeChunk $knowledgeChunk): bool
    {
        return $user->can('restore_knowledge::chunk');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, KnowledgeChunk $knowledgeChunk): bool
    {
        return $user->can('force_delete_knowledge::chunk');
    }
}
