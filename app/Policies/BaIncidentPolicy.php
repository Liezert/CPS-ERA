<?php

namespace App\Policies;

use App\Models\BaIncident;
use App\Models\User;

class BaIncidentPolicy
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
     * - Admin & Quality: Lintas seluruh divisi.
     * - Supervisor: Divisinya sendiri.
     * - Employee: BA buatannya sendiri atau BA di divisinya.
     */
    public function view(User $user, BaIncident $baIncident): bool
    {
        if ($user->hasAnyRole(['admin', 'quality'])) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return (int) $user->division_id === (int) $baIncident->division_id;
        }

        return $baIncident->created_by === $user->id || (int) $user->division_id === (int) $baIncident->division_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BaIncident $baIncident): bool
    {
        if (! $baIncident->isCreated()) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return (int) $user->division_id === (int) $baIncident->division_id;
        }

        return $baIncident->created_by === $user->id;
    }

    /**
     * Determine whether the user can review the model (Created -> Reviewed).
     * - Admin & Quality: Semua divisi.
     * - Supervisor: Hanya divisi sendiri.
     * - Employee: Dilarang.
     */
    public function review(User $user, BaIncident $baIncident): bool
    {
        if (! $baIncident->isCreated()) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'quality'])) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return (int) $user->division_id === (int) $baIncident->division_id;
        }

        return false;
    }

    /**
     * Determine whether the user can close the model (Reviewed -> Closed).
     * - Admin & Quality: Semua divisi.
     * - Supervisor: Hanya divisi sendiri.
     * - Employee: Dilarang.
     */
    public function close(User $user, BaIncident $baIncident): bool
    {
        if (! $baIncident->isReviewed()) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'quality'])) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return (int) $user->division_id === (int) $baIncident->division_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BaIncident $baIncident): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BaIncident $baIncident): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BaIncident $baIncident): bool
    {
        return $user->hasRole('admin');
    }
}
