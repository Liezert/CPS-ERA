<?php

namespace App\Policies;

use App\Enums\BaIncidentStatus;
use App\Models\BaIncident;
use App\Models\User;

class BaIncidentPolicy
{
    /**
     * Role yang boleh approve/reject di tahap HR (final). Juga dipakai sebagai daftar penerima
     * notifikasi "menunggu review HR" supaya keduanya tidak pernah berbeda.
     *
     * [TERBUKA — konfirmasi client sebelum go-live] Admin ikut approve bersama quality
     * (mempertahankan perilaku lama), atau dibatasi quality saja.
     */
    public const HR_REVIEWER_ROLES = ['quality', 'admin'];

    /**
     * Ability approve/reject per tahap. Dikecualikan dari bypass admin di Gate::before
     * (AppServiceProvider) supaya syarat status dan tahap juga berlaku untuk admin.
     */
    public const STAGE_REVIEW_ABILITIES = ['reviewAsSupervisor', 'reviewAsHr'];

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
        if (! $baIncident->isEditable()) {
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
     * Approve/reject tahap 1: Supervisor divisi pelapor, hanya saat laporan menunggu Supervisor.
     */
    public function reviewAsSupervisor(User $user, BaIncident $baIncident): bool
    {
        return $baIncident->status === BaIncidentStatus::PendingSupervisor->value
            && $user->hasRole('supervisor')
            && (int) $user->division_id === (int) $baIncident->division_id;
    }

    /**
     * Approve/reject tahap 2 (final): tim HR lintas divisi, hanya saat laporan menunggu HR.
     * Four-eyes: orang yang sudah menyetujui tahap Supervisor tidak boleh memutus tahap HR,
     * apa pun role-nya (termasuk user yang punya role supervisor dan quality sekaligus).
     */
    public function reviewAsHr(User $user, BaIncident $baIncident): bool
    {
        return $baIncident->status === BaIncidentStatus::PendingHr->value
            && $user->hasAnyRole(self::HR_REVIEWER_ROLES)
            && (int) $user->id !== (int) $baIncident->supervisor_reviewed_by;
    }

    /**
     * Riwayat aktivitas (audit trail) bersifat internal: hanya reviewer yang boleh melihatnya,
     * bukan pelapor maupun rekan satu divisinya. Alasan penolakan tetap sampai ke pelapor lewat
     * banner "Catatan Reviewer" (`catatan_penolakan`).
     */
    public function viewActivityLog(User $user, BaIncident $baIncident): bool
    {
        return $user->hasAnyRole(['supervisor', 'quality', 'admin']) && $this->view($user, $baIncident);
    }

    /**
     * Tim HR boleh menghapus rekaman video arsip dari BA yang ditolak permanen.
     */
    public function deleteArchivedVideo(User $user, BaIncident $baIncident): bool
    {
        return $baIncident->isRejected()
            && $baIncident->video !== null
            && $user->hasAnyRole(self::HR_REVIEWER_ROLES);
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
