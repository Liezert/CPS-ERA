<?php

namespace App\Models;

use Database\Factories\BaIncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'nomor_ba',
    'title',
    'description',
    'division_id',
    'tanggal_pengisian',
    'sumber_ketidaksesuaian',
    'sumber_ketidaksesuaian_lainnya',
    'tanggal_masalah',
    'lokasi',
    'deskripsi_masalah',
    'why_1',
    'why_2',
    'why_3',
    'why_4',
    'why_5',
    'kesimpulan_akar_masalah',
    'koreksi_deskripsi',
    'koreksi_pic',
    'koreksi_waktu',
    'korektif_deskripsi',
    'korektif_pic',
    'korektif_waktu',
    'is_potensi_risiko',
    'is_potensi_peluang',
    'status_verifikasi',
    'bukti_objektif',
    'alasan_tidak_efektif',
    'catatan_penolakan',
    'status',
    'created_by',
    'reviewed_by',
    'reviewed_at',
    'closed_at',
])]
class BaIncident extends Model implements HasMedia
{
    /** @use HasFactory<BaIncidentFactory> */
    use HasFactory, HasUuids, InteractsWithMedia;

    /**
     * Sumber ketidaksesuaian constants sesuai FR/QC/22
     */
    public const SUMBER_OPTIONS = [
        'keluhan_pelanggan' => 'Keluhan Pelanggan',
        'audit' => 'Audit',
        'laporan_ketidaksesuaian' => 'Laporan Ketidaksesuaian',
        'pencapaian_sasaran_program' => 'Pencapaian Sasaran Program',
        'lain_lain' => 'Lain-lain',
    ];

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('ba_file')->singleFile();
        $this->addMediaCollection('ftk_file')->singleFile();
    }

    /**
     * Get the division that the incident belongs to.
     *
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the user who created the incident.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who reviewed the incident.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the video contribution linked to this BA.
     *
     * @return HasOne<Video, $this>
     */
    public function video(): HasOne
    {
        return $this->hasOne(Video::class, 'ba_incident_id');
    }

    /**
     * Get all videos linked to this BA.
     *
     * @return HasMany<Video, $this>
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'ba_incident_id');
    }

    /**
     * Get all activity logs for the incident.
     *
     * @return HasMany<BaActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(BaActivityLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the lesson learned knowledge document generated from this incident.
     *
     * @return HasOne<KnowledgeDocument, $this>
     */
    public function lessonLearned(): HasOne
    {
        return $this->hasOne(KnowledgeDocument::class, 'source_ba_id');
    }

    /**
     * Status helper methods.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Backward-compatibility status checks.
     */
    public function isCreated(): bool
    {
        return in_array($this->status, ['draft', 'created', 'submitted'], true);
    }

    public function isReviewed(): bool
    {
        return in_array($this->status, ['reviewed', 'approved'], true);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'approved'], true);
    }

    /**
     * Backward-compatibility accessors for dropped file columns.
     */
    public function getFileBaUrlAttribute(): ?string
    {
        return $this->attributes['file_ba_url'] ?? $this->getFirstMediaUrl('ba_file') ?: null;
    }

    public function getFileFtkUrlAttribute(): ?string
    {
        return $this->attributes['file_ftk_url'] ?? $this->getFirstMediaUrl('ftk_file') ?: null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_pengisian' => 'date',
            'tanggal_masalah' => 'date',
            'is_potensi_risiko' => 'boolean',
            'is_potensi_peluang' => 'boolean',
            'reviewed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
