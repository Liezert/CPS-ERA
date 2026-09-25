<?php

namespace App\Observers;

use App\Enums\BaIncidentStatus;
use App\Models\BaIncident;
use App\Models\Notification;
use App\Models\User;
use App\Policies\BaIncidentPolicy;
use Illuminate\Support\Collection;

/**
 * Notifikasi bell untuk alur approval CAPA dua tahap, dipicu oleh perubahan status. Notifikasi
 * ditulis di transaksi yang sama dengan transisinya, jadi ikut batal kalau transisi gagal.
 */
class BaIncidentObserver
{
    public function updated(BaIncident $ba): void
    {
        if (! $ba->wasChanged('status')) {
            return;
        }

        match ($ba->status) {
            BaIncidentStatus::PendingSupervisor->value => $this->notifySupervisors($ba),
            BaIncidentStatus::PendingHr->value => $this->notifyHrReviewers($ba),
            BaIncidentStatus::RevisionRequested->value => $this->notifyReporterOfRevision($ba),
            BaIncidentStatus::Rejected->value => $this->notifyApprovingSupervisorOfRejection($ba),
            default => null,
        };
    }

    /**
     * Laporan baru atau hasil revisi masuk antrean Supervisor divisi pelapor.
     */
    protected function notifySupervisors(BaIncident $ba): void
    {
        $isResubmit = $ba->getOriginal('status') === BaIncidentStatus::RevisionRequested->value;

        $this->send(
            User::role('supervisor')->where('division_id', $ba->division_id)->get(),
            $ba,
            'ba_review',
            ($isResubmit ? 'BA Revisi Menunggu Review: ' : 'BA Menunggu Review: ').$ba->nomor_ba,
            $isResubmit
                ? "Laporan {$ba->nomor_ba} sudah direvisi pelapor dan dikirim ulang untuk Anda tinjau."
                : "Laporan insiden {$ba->nomor_ba} di divisi Anda memerlukan peninjauan.",
        );
    }

    /**
     * Laporan disetujui Supervisor dan menunggu review final tim HR. Supervisor yang menyetujui
     * dikecualikan karena four-eyes melarangnya memutus tahap HR.
     */
    protected function notifyHrReviewers(BaIncident $ba): void
    {
        $this->send(
            User::role(BaIncidentPolicy::HR_REVIEWER_ROLES)
                ->when($ba->supervisor_reviewed_by, fn ($query, $supervisorId) => $query->whereKeyNot($supervisorId))
                ->get(),
            $ba,
            'ba_review',
            'BA Menunggu Review Final HR: '.$ba->nomor_ba,
            "Laporan {$ba->nomor_ba} sudah disetujui Supervisor dan menunggu review final tim HR.",
        );
    }

    /**
     * Pelapor perlu tahu laporannya dikembalikan Supervisor untuk direvisi, beserta alasannya.
     */
    protected function notifyReporterOfRevision(BaIncident $ba): void
    {
        $this->send(
            User::whereKey($ba->created_by)->get(),
            $ba,
            'ba_revisi',
            'BA Perlu Revisi: '.$ba->nomor_ba,
            "Laporan {$ba->nomor_ba} dikembalikan Supervisor untuk direvisi. Catatan: {$ba->catatan_penolakan}",
        );
    }

    /**
     * Supervisor yang meneruskan laporan perlu tahu kalau HR menolaknya secara permanen.
     */
    protected function notifyApprovingSupervisorOfRejection(BaIncident $ba): void
    {
        if ($ba->getOriginal('status') !== BaIncidentStatus::PendingHr->value || ! $ba->supervisor_reviewed_by) {
            return;
        }

        $this->send(
            User::whereKey($ba->supervisor_reviewed_by)->get(),
            $ba,
            'ba_ditolak_hr',
            'BA Ditolak Permanen oleh HR: '.$ba->nomor_ba,
            "Laporan {$ba->nomor_ba} yang Anda setujui ditolak permanen oleh tim HR. Alasan: {$ba->catatan_penolakan}",
        );
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    protected function send(Collection $recipients, BaIncident $ba, string $type, string $title, string $message): void
    {
        foreach ($recipients as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'related_type' => 'ba_incident',
                'related_id' => (string) $ba->id,
                'read_at' => null,
            ]);
        }
    }
}
