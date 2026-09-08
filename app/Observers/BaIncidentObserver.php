<?php

namespace App\Observers;

use App\Models\BaIncident;
use App\Models\Notification;
use App\Models\User;

class BaIncidentObserver
{
    /**
     * Handle the BaIncident "created" event.
     * Mengirim notifikasi 'ba_review' ke supervisor di divisi terkait saat ada BA baru dibuat.
     */
    public function created(BaIncident $ba): void
    {
        if ($ba->status === 'created') {
            $this->notifySupervisors($ba);
        }
    }

    /**
     * Notify supervisors in the BA incident's division.
     */
    protected function notifySupervisors(BaIncident $ba): void
    {
        $supervisors = User::role('supervisor')
            ->where('division_id', $ba->division_id)
            ->get();

        foreach ($supervisors as $supervisor) {
            Notification::create([
                'user_id' => $supervisor->id,
                'type' => 'ba_review',
                'title' => 'BA Menunggu Review: '.$ba->nomor_ba,
                'message' => "Laporan insiden {$ba->nomor_ba} di divisi Anda memerlukan peninjauan.",
                'related_type' => 'ba_incident',
                'related_id' => (string) $ba->id,
                'read_at' => null,
            ]);
        }
    }
}
