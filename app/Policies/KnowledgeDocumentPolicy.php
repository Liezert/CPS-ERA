<?php

namespace App\Policies;

use App\Models\KnowledgeDocument;
use App\Models\User;

class KnowledgeDocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     * Sesuai PRD 2.2: Semua role (Employee, Supervisor, Quality, Admin) boleh melihat & mencari materi.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Sesuai PRD 2.2: Semua role boleh melihat detail materi.
     */
    public function view(User $user, KnowledgeDocument $knowledgeDocument): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Sesuai PRD 2.2: Semua role boleh menambahkan materi (Dokumen/Video/dll).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * - Admin & Quality: Semua divisi.
     * - Supervisor: Divisi sendiri.
     * - Employee: Hanya materi yang dibuatnya sendiri saat masih status draft.
     */
    public function update(User $user, KnowledgeDocument $knowledgeDocument): bool
    {
        if ($user->hasAnyRole(['admin', 'quality'])) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return (int) $user->division_id === (int) $knowledgeDocument->division_id;
        }

        if ($user->hasRole('employee')) {
            return (int) $user->id === (int) $knowledgeDocument->created_by
                && $knowledgeDocument->status === 'draft';
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * Sesuai PRD 2.2 (Access Matrix):
     * - Employee: ❌ Dilarang (false)
     * - Supervisor: Divisinya saja
     * - Quality: ✅ Semua divisi
     * - Admin: ✅ Semua divisi
     */
    public function delete(User $user, KnowledgeDocument $knowledgeDocument): bool
    {
        if ($user->hasAnyRole(['admin', 'quality'])) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return (int) $user->division_id === (int) $knowledgeDocument->division_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, KnowledgeDocument $knowledgeDocument): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, KnowledgeDocument $knowledgeDocument): bool
    {
        return $user->hasRole('admin');
    }
}
