<?php

namespace App\Policies;

use App\Models\TvInterview;
use App\Models\User;

class TvInterviewPolicy
{
    // Interview entries share the existing video content permissions, so
    // current content managers can use this dedicated library without role edits.
    public function viewAny(User $user): bool { return $user->can('view_any_video'); }
    public function view(User $user, TvInterview $interview): bool { return $user->can('view_video'); }
    public function create(User $user): bool { return $user->can('create_video'); }
    public function update(User $user, TvInterview $interview): bool { return $user->can('update_video'); }
    public function delete(User $user, TvInterview $interview): bool { return $user->can('delete_video'); }
    public function deleteAny(User $user): bool { return $user->can('delete_video'); }
    public function reorder(User $user): bool { return $user->can('update_video'); }
}
