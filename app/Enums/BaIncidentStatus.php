<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Status alur approval CAPA dua tahap: Supervisor divisi pelapor → HR (final).
 *
 * Sengaja TIDAK dipasang sebagai cast di BaIncident: kolom `status` tetap string biasa dan
 * banyak kode lama masih membandingkan string mentah (`=== 'draft'`). Pakai `->value`
 * saat membandingkan atau menulis status.
 */
enum BaIncidentStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case PendingSupervisor = 'pending_supervisor';
    case RevisionRequested = 'revision_requested';
    case PendingHr = 'pending_hr';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::PendingSupervisor => 'Menunggu Review Supervisor',
            self::RevisionRequested => 'Perlu Revisi',
            self::PendingHr => 'Menunggu Review HR',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak HR',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingSupervisor, self::PendingHr => 'info',
            self::RevisionRequested => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
