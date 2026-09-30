<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;

/**
 * Video kontribusi (keputusan owner 2026-09-27): siapa pun boleh mengunggah, HANYA HR/HRGA yang
 * menyetujui atau menolak. Setelah disetujui, video tampil untuk semua karyawan lewat Learning.
 */
class VideoPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Halaman detail video: pengunggahnya sendiri dan tim HR. Video yang sudah terbit
     * boleh dibuka semua karyawan.
     */
    public function view(User $user, Video $video): bool
    {
        return $video->isPublished()
            || (int) $user->id === (int) $video->created_by
            || $user->hasAnyRole(BaIncidentPolicy::HR_REVIEWER_ROLES);
    }

    /**
     * Approve/tolak: tim HR, selama video menunggu review HR. Pengunggah tidak boleh
     * memutus videonya sendiri, walaupun ia anggota tim HR.
     */
    public function review(User $user, Video $video): bool
    {
        return $video->isPendingHr()
            && $user->hasAnyRole(BaIncidentPolicy::HR_REVIEWER_ROLES)
            && (int) $user->id !== (int) $video->created_by;
    }
}
