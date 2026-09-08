<?php

namespace App\Policies;

use App\Models\LearningCategory;
use App\Models\User;

class LearningCategoryPolicy
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
    public function view(User $user, LearningCategory $learningCategory): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Sesuai PRD 2.2: Hanya role Quality dan Admin yang boleh mengelola kategori Learning.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'quality']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LearningCategory $learningCategory): bool
    {
        return $user->hasAnyRole(['admin', 'quality']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LearningCategory $learningCategory): bool
    {
        return $user->hasAnyRole(['admin', 'quality']);
    }
}
