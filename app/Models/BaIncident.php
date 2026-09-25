<?php

namespace App\Models;

use App\Enums\BaIncidentStatus;
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
    'supervisor_reviewed_by',
    'supervisor_reviewed_at',
    'points_awarded_at',
    'published_at',
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
     * Panduan pengisian field CAPA: helper text singkat + kerangka struktur isian untuk tooltip.
     * Dipakai form Blade (components/capa/form/*) dan form Filament admin, jadi cukup diubah di sini.
     * Sengaja berupa kerangka, bukan contoh kalimat, supaya tidak memancing copy-paste.
     *
     * @var array<string, array{helper: string, guide: array<string, string>}>
     */
    public const FIELD_GUIDES = [
        'deskripsi_masalah' => [
            'helper' => 'Sebutkan objek, nilai penyimpangan dari standar, dan dampak langsungnya.',
            'guide' => [
                'Objek/Proses' => 'Komponen atau lini yang bermasalah.',
                'Deviasi' => 'Nilai/kondisi aktual vs standar spesifikasi.',
                'Dampak' => 'Akibat langsung terhadap operasional/kualitas.',
            ],
        ],
        'why_1' => [
            'helper' => 'Tulis penyebab langsung yang memicu masalah di atas.',
            'guide' => [
                'Penyebab langsung' => 'Kondisi yang secara langsung memicu masalah.',
                'Berbasis fakta' => 'Hal yang teramati atau terukur, bukan dugaan.',
                'Why berikutnya' => 'Tanyakan "mengapa" lagi pada jawaban ini sampai akar masalah ditemukan.',
            ],
        ],
        'kesimpulan_akar_masalah' => [
            'helper' => 'Penyebab paling dasar dari rantai Why yang akan diselesaikan tindakan korektif.',
            'guide' => [
                'Akar masalah' => 'Penyebab terdalam dari rantai Why di atas.',
                'Dapat dikendalikan' => 'Bisa diperbaiki lewat perubahan proses, SOP, atau sistem.',
                'Uji balik' => 'Jika penyebab ini dihilangkan, masalah tidak terulang.',
            ],
        ],
        'koreksi_deskripsi' => [
            'helper' => 'Langkah darurat untuk menahan dampak, bukan perbaikan permanen.',
            'guide' => [
                'Tindakan' => 'Langkah segera yang dilakukan di lokasi.',
                'Objek' => 'Mesin, batch produk, atau area yang diamankan.',
                'Status' => 'Kondisi setelah tindakan dilakukan.',
            ],
        ],
        'korektif_deskripsi' => [
            'helper' => 'Perbaikan permanen yang menghilangkan akar masalah.',
            'guide' => [
                'Perubahan' => 'Proses, SOP, atau peralatan yang diubah.',
                'Sasaran' => 'Akar masalah pada Bagian 4 yang dihilangkan.',
                'Verifikasi' => 'Cara memastikan masalah tidak terulang.',
            ],
        ],
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
     * Get all activity logs for the incident.
     *
     * @return HasMany<BaActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(BaActivityLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Materi Learning hasil laporan ini (dibuat saat approval HR, terbit setelah post-test dibuat).
     *
     * @return HasOne<LearningMaterial, $this>
     */
    public function learningMaterial(): HasOne
    {
        return $this->hasOne(LearningMaterial::class, 'source_ba_id');
    }

    /**
     * Isi laporan hanya boleh diubah saat masih draf atau sedang diminta revisi.
     * Laporan yang sedang di-review atau sudah final tidak boleh ditarik kembali ke draf.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [
            BaIncidentStatus::Draft->value,
            BaIncidentStatus::RevisionRequested->value,
        ], true);
    }

    public function isRevisionRequested(): bool
    {
        return $this->status === BaIncidentStatus::RevisionRequested->value;
    }

    /**
     * Ditolak permanen oleh HR (final, tanpa jalur revisi).
     */
    public function isRejected(): bool
    {
        return $this->status === BaIncidentStatus::Rejected->value;
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
     * Nilai form CAPA untuk komponen bersama components/capa/form/* (mode readonly).
     * Kuncinya sama dengan Livewire\Ba\Create::capaFormValues() yang dipakai mode edit.
     *
     * @return array<string, mixed>
     */
    public function capaFormValues(): array
    {
        return [
            'nomorBaPreview' => $this->nomor_ba,
            'divisionId' => $this->division_id,
            'tanggalPengisian' => $this->tanggal_pengisian?->format('Y-m-d') ?? '',
            'sumberKetidaksesuaian' => $this->sumber_ketidaksesuaian ?? 'laporan_ketidaksesuaian',
            'sumberKetidaksesuaianLainnya' => $this->sumber_ketidaksesuaian_lainnya ?? '',
            'tanggalMasalah' => $this->tanggal_masalah?->format('Y-m-d') ?? '',
            'lokasi' => $this->lokasi ?? '',
            'deskripsiMasalah' => $this->deskripsi_masalah ?? ($this->description ?? ''),
            'why1' => $this->why_1 ?? '',
            'why2' => $this->why_2 ?? '',
            'why3' => $this->why_3 ?? '',
            'why4' => $this->why_4 ?? '',
            'why5' => $this->why_5 ?? '',
            'kesimpulanAkarMasalah' => $this->kesimpulan_akar_masalah ?? '',
            'koreksiDeskripsi' => $this->koreksi_deskripsi ?? '',
            'koreksiPic' => $this->koreksi_pic ?? '',
            'koreksiWaktu' => $this->koreksi_waktu ?? '',
            'korektifDeskripsi' => $this->korektif_deskripsi ?? '',
            'korektifPic' => $this->korektif_pic ?? '',
            'korektifWaktu' => $this->korektif_waktu ?? '',
            'isPotensiRisiko' => (bool) $this->is_potensi_risiko,
            'isPotensiPeluang' => (bool) $this->is_potensi_peluang,
        ];
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
            'supervisor_reviewed_at' => 'datetime',
            'points_awarded_at' => 'datetime',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
