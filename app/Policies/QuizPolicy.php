<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Quiz $quiz): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Sesuai PRD 2.2: Hanya role Quality dan Admin yang boleh membuat quiz/misi baru.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'quality']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Quiz $quiz): bool
    {
        return $user->hasAnyRole(['admin', 'quality']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->hasAnyRole(['admin', 'quality']);
    }
}
