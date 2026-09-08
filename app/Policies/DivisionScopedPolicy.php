<?php

namespace App\Policies;

use App\Models\User;

class DivisionScopedPolicy
{
    /**
     * Determine whether the user can view the record.
     *
     * Pola dasar division scoping:
     * - Admin: Memiliki akses bypass ke seluruh divisi.
     * - Supervisor / Quality: Hanya diizinkan melihat record di divisinya sendiri.
     * - Employee: Diizinkan jika berada di divisi yang sama.
     */
    public function view(User $user, object $record): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $this->isSameDivision($user, $record);
    }

    /**
     * Determine whether the user can update the record.
     */
    public function update(User $user, object $record): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('supervisor') || $user->hasRole('quality')) {
            return $this->isSameDivision($user, $record);
        }

        return false;
    }

    /**
     * Determine whether the user can review the record.
     *
     * Mengacu ke PRD 2.2:
     * - Admin & Quality: Validasi lintas divisi (semua divisi).
     * - Supervisor: Review hanya untuk divisi sendiri.
     */
    public function review(User $user, object $record): bool
    {
        if ($user->hasAnyRole(['admin', 'quality'])) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return $this->isSameDivision($user, $record);
        }

        return false;
    }

    /**
     * Determine whether the user and record share the same division.
     */
    public function isSameDivision(User $user, object $record): bool
    {
        $recordDivisionId = $record->division_id ?? null;

        if ($recordDivisionId === null || $user->division_id === null) {
            return false;
        }

        return (int) $user->division_id === (int) $recordDivisionId;
    }
}
