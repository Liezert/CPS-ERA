<?php

namespace App\Policies;

use App\Models\LearningMaterial;
use App\Models\User;

class LearningMaterialPolicy
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
    public function view(User $user, LearningMaterial $learningMaterial): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Sesuai PRD 2.2: Semua role boleh membuat materi (Employee status draft).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LearningMaterial $learningMaterial): bool
    {
        if ($user->hasAnyRole(['admin', 'quality'])) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return (int) $user->id === (int) $learningMaterial->created_by
                || (int) $user->division_id === (int) $learningMaterial->creator?->division_id;
        }

        return (int) $user->id === (int) $learningMaterial->created_by && $learningMaterial->status === 'draft';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LearningMaterial $learningMaterial): bool
    {
        if ($user->hasAnyRole(['admin', 'quality'])) {
            return true;
        }

        return (int) $user->id === (int) $learningMaterial->created_by && $learningMaterial->status === 'draft';
    }
}
