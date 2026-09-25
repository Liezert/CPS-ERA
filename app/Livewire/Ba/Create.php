<?php

namespace App\Livewire\Ba;

use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use App\Services\BaIncidentService;
use App\Services\GoogleDriveService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Buat Laporan BA & CAPA Digital - CPS ERA')]
class Create extends Component
{
    use WithFileUploads;

    public int $step = 1;

    // Terkunci: ID laporan hanya boleh diisi server (mount/penyimpanan), bukan diubah dari klien.
    #[Url, Locked]
    public ?string $incidentId = null;

    #[Locked]
    public ?string $baIncidentId = null;

    public string $nomorBaPreview = '';

    public ?int $divisionId = null;

    public string $tanggalPengisian = '';

    public string $sumberKetidaksesuaian = 'laporan_ketidaksesuaian';

    public string $sumberKetidaksesuaianLainnya = '';

    public string $tanggalMasalah = '';

    public string $lokasi = '';

    public string $title = '';

    public string $deskripsiMasalah = '';

    public string $why1 = '';

    public string $why2 = '';

    public string $why3 = '';

    public string $why4 = '';

    public string $why5 = '';

    public string $kesimpulanAkarMasalah = '';

    public string $koreksiDeskripsi = '';

    public string $koreksiPic = '';

    public string $koreksiWaktu = '';

    public string $korektifDeskripsi = '';

    public string $korektifPic = '';

    public string $korektifWaktu = '';

    public bool $isPotensiRisiko = false;

    public bool $isPotensiPeluang = false;

    // Step 2: Video Penanganan
    public string $videoMethod = 'file'; // 'file' | 'link'

    public $videoFile = null;

    public string $videoExternalLink = '';

    // Resubmit: video yang sudah pernah dikirim ke Drive dipakai lagi kalau tidak diganti.
    public bool $hasExistingDriveVideo = false;

    public string $videoTitle = '';

    /** Field Langkah 1 yang disalin ke localStorage dan boleh dipulihkan lewat restoreLocalDraft(). */
    public const LOCAL_DRAFT_FIELDS = [
        'divisionId', 'tanggalPengisian', 'sumberKetidaksesuaian', 'sumberKetidaksesuaianLainnya',
        'tanggalMasalah', 'lokasi', 'deskripsiMasalah', 'why1', 'why2', 'why3', 'why4', 'why5',
        'kesimpulanAkarMasalah', 'koreksiDeskripsi', 'koreksiPic', 'koreksiWaktu',
        'korektifDeskripsi', 'korektifPic', 'korektifWaktu', 'isPotensiRisiko', 'isPotensiPeluang',
    ];

    public function mount(BaIncidentService $service, ?string $incidentId = null): void
    {
        $user = Auth::user();
        // User HRGA tidak punya divisi pelapor bawaan; ia harus memilih divisi secara eksplisit.
        $this->divisionId = Division::reportable()->whereKey($user?->division_id)->value('id');
        $this->tanggalPengisian = now()->format('Y-m-d');
        $this->tanggalMasalah = now()->format('Y-m-d');

        // Jika ada parameter edit/resubmit
        $targetId = $incidentId ?? $this->incidentId;
        if ($targetId) {
            $existing = BaIncident::with('video')->find($targetId);
            if ($existing && ($existing->created_by === $user?->id || $user?->hasRole('admin'))) {
                abort_unless($existing->isEditable(), 403, "Laporan {$existing->nomor_ba} sedang dalam proses review atau sudah final, sehingga tidak bisa diubah.");

                $this->baIncidentId = $existing->id;
                $this->nomorBaPreview = $existing->nomor_ba;
                $this->divisionId = $existing->division_id;
                $this->title = $existing->title ?? '';
                $this->tanggalPengisian = $existing->tanggal_pengisian ? $existing->tanggal_pengisian->format('Y-m-d') : now()->format('Y-m-d');
                $this->sumberKetidaksesuaian = $existing->sumber_ketidaksesuaian ?? 'laporan_ketidaksesuaian';
                $this->sumberKetidaksesuaianLainnya = $existing->sumber_ketidaksesuaian_lainnya ?? '';
                $this->tanggalMasalah = $existing->tanggal_masalah ? $existing->tanggal_masalah->format('Y-m-d') : now()->format('Y-m-d');
                $this->lokasi = $existing->lokasi ?? '';
                $this->deskripsiMasalah = $existing->deskripsi_masalah ?? ($existing->description ?? '');
                $this->why1 = $existing->why_1 ?? '';
                $this->why2 = $existing->why_2 ?? '';
                $this->why3 = $existing->why_3 ?? '';
                $this->why4 = $existing->why_4 ?? '';
                $this->why5 = $existing->why_5 ?? '';
                $this->kesimpulanAkarMasalah = $existing->kesimpulan_akar_masalah ?? '';
                $this->koreksiDeskripsi = $existing->koreksi_deskripsi ?? '';
                $this->koreksiPic = $existing->koreksi_pic ?? '';
                $this->koreksiWaktu = $existing->koreksi_waktu ?? '';
                $this->korektifDeskripsi = $existing->korektif_deskripsi ?? '';
                $this->korektifPic = $existing->korektif_pic ?? '';
                $this->korektifWaktu = $existing->korektif_waktu ?? '';
                $this->isPotensiRisiko = (bool) $existing->is_potensi_risiko;
                $this->isPotensiPeluang = (bool) $existing->is_potensi_peluang;

                if ($existing->video) {
                    $this->hasExistingDriveVideo = GoogleDriveService::isDriveFileId($existing->video->video_file_url);
                    $this->videoExternalLink = $existing->video->video_external_link ?? '';
                    if (! empty($existing->video->video_external_link)) {
                        $this->videoMethod = 'link';
                    }
                }

                return;
            }
        }

        // Auto-generate nomor BA format BA-YYYY-NNNN untuk ditampilkan read-only
        $this->nomorBaPreview = $service->generateNomorBa();
    }

    /**
     * Validasi Langkah 1 (Formulir CAPA)
     */
    protected function stepOneRules(): array
    {
        return [
            'divisionId' => ['required', Rule::exists('divisions', 'id')->whereNot('name', Division::HRGA)],
            'tanggalPengisian' => ['required', 'date'],
            'sumberKetidaksesuaian' => ['required', 'string', 'in:keluhan_pelanggan,audit,laporan_ketidaksesuaian,pencapaian_sasaran_program,lain_lain'],
            'sumberKetidaksesuaianLainnya' => ['nullable', 'string', 'max:255', 'required_if:sumberKetidaksesuaian,lain_lain'],
            'tanggalMasalah' => ['required', 'date'],
            'lokasi' => ['required', 'string', 'max:255'],
            'deskripsiMasalah' => ['required', 'string', 'min:5'],
            'why1' => ['required', 'string', 'min:3'],
            'why2' => ['nullable', 'string'],
            'why3' => ['nullable', 'string'],
            'why4' => ['nullable', 'string'],
            'why5' => ['nullable', 'string'],
            'kesimpulanAkarMasalah' => ['required', 'string', 'min:5'],
            'koreksiDeskripsi' => ['required', 'string', 'min:5'],
            'koreksiPic' => ['nullable', 'string', 'max:150'],
            'koreksiWaktu' => ['nullable', 'string', 'max:100'],
            'korektifDeskripsi' => ['required', 'string', 'min:5'],
            'korektifPic' => ['nullable', 'string', 'max:150'],
            'korektifWaktu' => ['nullable', 'string', 'max:100'],
            'isPotensiRisiko' => ['boolean'],
            'isPotensiPeluang' => ['boolean'],
        ];
    }

    protected function stepOneMessages(): array
    {
        return [
            'divisionId.required' => 'Divisi wajib dipilih.',
            'tanggalMasalah.required' => 'Tanggal kejadian masalah wajib diisi.',
            'lokasi.required' => 'Lokasi kejadian wajib diisi.',
            'deskripsiMasalah.required' => 'Deskripsi rincian masalah wajib diisi.',
            'why1.required' => 'Analisis Why pertama wajib diisi.',
            'kesimpulanAkarMasalah.required' => 'Kesimpulan akar masalah wajib diisi.',
            'koreksiDeskripsi.required' => 'Deskripsi tindakan koreksi sementara wajib diisi.',
            'korektifDeskripsi.required' => 'Deskripsi tindakan korektif perbaikan wajib diisi.',
            'sumberKetidaksesuaianLainnya.required_if' => 'Rincian sumber ketidaksesuaian lainnya wajib disebutkan.',
        ];
    }

    /**
     * Pindah ke Langkah 2 (Video): validasi form CAPA & simpan status draft ke database.
     */
    public function nextStep(BaIncidentService $service): void
    {
        $this->validate($this->stepOneRules(), $this->stepOneMessages());

        $user = Auth::user();

        $data = [
            'division_id' => $this->divisionId,
            'title' => $this->title ?: ('CAPA: '.mb_substr($this->deskripsiMasalah, 0, 60)),
            'tanggal_pengisian' => $this->tanggalPengisian,
            'sumber_ketidaksesuaian' => $this->sumberKetidaksesuaian,
            'sumber_ketidaksesuaian_lainnya' => $this->sumberKetidaksesuaianLainnya,
            'tanggal_masalah' => $this->tanggalMasalah,
            'lokasi' => $this->lokasi,
            'deskripsi_masalah' => $this->deskripsiMasalah,
            'why_1' => $this->why1,
            'why_2' => $this->why2,
            'why_3' => $this->why3,
            'why_4' => $this->why4,
            'why_5' => $this->why5,
            'kesimpulan_akar_masalah' => $this->kesimpulanAkarMasalah,
            'koreksi_deskripsi' => $this->koreksiDeskripsi,
            'koreksi_pic' => $this->koreksiPic,
            'koreksi_waktu' => $this->koreksiWaktu,
            'korektif_deskripsi' => $this->korektifDeskripsi,
            'korektif_pic' => $this->korektifPic,
            'korektif_waktu' => $this->korektifWaktu,
            'is_potensi_risiko' => $this->isPotensiRisiko,
            'is_potensi_peluang' => $this->isPotensiPeluang,
        ];

        $incident = $this->persistDraft($service, $data, $user);
        if (! $incident) {
            return;
        }

        $this->rememberSavedDraft($incident);

        $this->step = 2;
    }

    /**
     * Simpan Draf Cepat tanpa harus pindah ke Langkah 2 (Video).
     */
    public function saveDraftOnly(BaIncidentService $service): void
    {
        $this->validate([
            'divisionId' => ['required', Rule::exists('divisions', 'id')->whereNot('name', Division::HRGA)],
        ], [
            'divisionId.required' => 'Divisi wajib dipilih untuk menyimpan draf.',
        ]);

        $user = Auth::user();

        $data = [
            'division_id' => $this->divisionId,
            'title' => $this->title ?: ('CAPA: '.mb_substr($this->deskripsiMasalah ?: 'Draf Laporan Insiden', 0, 60)),
            'tanggal_pengisian' => $this->tanggalPengisian ?: now()->format('Y-m-d'),
            'sumber_ketidaksesuaian' => $this->sumberKetidaksesuaian,
            'sumber_ketidaksesuaian_lainnya' => $this->sumberKetidaksesuaianLainnya,
            'tanggal_masalah' => $this->tanggalMasalah ?: now()->format('Y-m-d'),
            'lokasi' => $this->lokasi,
            'deskripsi_masalah' => $this->deskripsiMasalah,
            'why_1' => $this->why1,
            'why_2' => $this->why2,
            'why_3' => $this->why3,
            'why_4' => $this->why4,
            'why_5' => $this->why5,
            'kesimpulan_akar_masalah' => $this->kesimpulanAkarMasalah,
            'koreksi_deskripsi' => $this->koreksiDeskripsi,
            'koreksi_pic' => $this->koreksiPic,
            'koreksi_waktu' => $this->koreksiWaktu,
            'korektif_deskripsi' => $this->korektifDeskripsi,
            'korektif_pic' => $this->korektifPic,
            'korektif_waktu' => $this->korektifWaktu,
            'is_potensi_risiko' => $this->isPotensiRisiko,
            'is_potensi_peluang' => $this->isPotensiPeluang,
        ];

        $incident = $this->persistDraft($service, $data, $user);
        if (! $incident) {
            return;
        }

        $this->rememberSavedDraft($incident);

        session()->flash('success', "Draf laporan {$incident->nomor_ba} berhasil disimpan ke sistem.");
    }

    /**
     * Simpan isi form lewat service. Kalau status laporan berubah selama form terbuka
     * (mis. sudah masuk review), kembalikan pengguna ke halaman detail dengan penjelasan.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persistDraft(BaIncidentService $service, array $data, User $user): ?BaIncident
    {
        try {
            return $service->saveDraft($data, $user, $this->baIncidentId);
        } catch (DomainException $exception) {
            session()->flash('status', $exception->getMessage());
            $this->redirect(route('ba.show', $this->baIncidentId), navigate: true);

            return null;
        }
    }

    /**
     * Setelah draf tersimpan: ID masuk ke URL (muat ulang halaman membuka draf yang sama dari database)
     * dan browser diberi tahu supaya salinan lokal isian pindah ke kunci draf ini.
     */
    protected function rememberSavedDraft(BaIncident $incident): void
    {
        $this->baIncidentId = $incident->id;
        $this->incidentId = $incident->id;
        $this->nomorBaPreview = $incident->nomor_ba;

        $this->dispatch('capa-draft-saved', id: $incident->id);
    }

    /**
     * Pulihkan isian Langkah 1 yang disimpan browser (localStorage) sebelum halaman dimuat ulang,
     * mis. karena jaringan terputus. Hanya field form CAPA yang diterima; validasi tetap berjalan
     * saat draf disimpan atau dilanjutkan ke Langkah 2.
     *
     * @param  array<string, mixed>  $values
     */
    public function restoreLocalDraft(array $values): void
    {
        if ($this->step !== 1) {
            return;
        }

        foreach (array_intersect_key($values, array_flip(self::LOCAL_DRAFT_FIELDS)) as $field => $value) {
            $this->{$field} = match (true) {
                is_bool($this->{$field}) => (bool) $value,
                $field === 'divisionId' => is_numeric($value) ? (int) $value : null,
                default => is_scalar($value) ? mb_substr((string) $value, 0, 5000) : '',
            };
        }
    }

    /**
     * Kembali ke Langkah 1 dari Langkah 2 tanpa kehilangan data.
     */
    public function previousStep(): void
    {
        $this->step = 1;
    }

    /**
     * Submit Final: Validasi Langkah 2 (Video wajib file/link), kirim ke antrean Supervisor (pending_supervisor).
     */
    public function submit(BaIncidentService $service)
    {
        $hasFile = ! empty($this->videoFile);
        $hasLink = ! empty(trim($this->videoExternalLink));

        if (! $hasFile && ! $hasLink && ! $this->hasExistingDriveVideo) {
            $this->addError('videoRequired', 'Salah satu dari berkas file video atau tautan link eksternal WAJIB diisi.');

            return;
        }

        if ($hasFile) {
            $this->validate([
                'videoFile' => ['file', 'mimes:mp4,mov,avi,mkv,webm', 'max:102400'], // 100MB
            ], [
                'videoFile.mimes' => 'Format file video harus mp4, mov, avi, mkv, atau webm.',
                'videoFile.max' => 'Ukuran file video maksimal 100MB.',
            ]);
        }

        if ($hasLink) {
            $this->validate([
                'videoExternalLink' => ['url', 'max:255'],
            ], [
                'videoExternalLink.url' => 'Format tautan link video eksternal harus berupa URL valid (contoh: https://drive.google.com/...).',
            ]);
        }

        $incident = BaIncident::findOrFail($this->baIncidentId);
        $user = Auth::user();

        $videoData = [
            'title' => $this->videoTitle ?: ('Video Penanganan: '.$incident->nomor_ba),
            'description' => 'Video dokumentasi penanganan dan perbaikan masalah untuk '.$incident->nomor_ba,
            'video_file' => $this->videoFile,
            'video_external_link' => $this->videoExternalLink,
        ];

        try {
            $service->submitWithVideo($incident, $user, $videoData);
        } catch (DomainException $exception) {
            // Unggahan Drive gagal: laporan tidak dikirim ke Supervisor,
            // draf tetap utuh, dan pengguna melihat alasannya.
            $this->addError('videoRequired', $exception->getMessage());

            return;
        }

        // Salinan isian di perangkat (localStorage) tidak diperlukan lagi setelah laporan terkirim.
        $this->dispatch('capa-draft-submitted');

        session()->flash('success', "Laporan Berita Acara {$incident->nomor_ba} beserta video penanganan berhasil dikirimkan dan menunggu peninjauan supervisor/admin.");

        return $this->redirect(route('ba.index'), navigate: true);
    }

    public function render()
    {
        $divisions = Division::reportable()->orderBy('id')->get();

        return view('livewire.ba.create', [
            'divisions' => $divisions,
            'sumberOptions' => BaIncident::SUMBER_OPTIONS,
            'capa' => $this->capaFormValues(),
            // Video yang sudah dikirim sebelumnya (resubmit) ikut dipratinjau di Langkah 2.
            'existingVideoIncident' => $this->hasExistingDriveVideo && $this->baIncidentId
                ? BaIncident::with('video')->find($this->baIncidentId)
                : null,
        ]);
    }

    /**
     * Nilai form untuk komponen bersama components/capa/form/* (mode edit).
     * Kuncinya sama dengan BaIncident::capaFormValues() yang dipakai mode readonly.
     *
     * @return array<string, mixed>
     */
    protected function capaFormValues(): array
    {
        return [
            'nomorBaPreview' => $this->nomorBaPreview,
            'divisionId' => $this->divisionId,
            'tanggalPengisian' => $this->tanggalPengisian,
            'sumberKetidaksesuaian' => $this->sumberKetidaksesuaian,
            'sumberKetidaksesuaianLainnya' => $this->sumberKetidaksesuaianLainnya,
            'tanggalMasalah' => $this->tanggalMasalah,
            'lokasi' => $this->lokasi,
            'deskripsiMasalah' => $this->deskripsiMasalah,
            'why1' => $this->why1,
            'why2' => $this->why2,
            'why3' => $this->why3,
            'why4' => $this->why4,
            'why5' => $this->why5,
            'kesimpulanAkarMasalah' => $this->kesimpulanAkarMasalah,
            'koreksiDeskripsi' => $this->koreksiDeskripsi,
            'koreksiPic' => $this->koreksiPic,
            'koreksiWaktu' => $this->koreksiWaktu,
            'korektifDeskripsi' => $this->korektifDeskripsi,
            'korektifPic' => $this->korektifPic,
            'korektifWaktu' => $this->korektifWaktu,
            'isPotensiRisiko' => $this->isPotensiRisiko,
            'isPotensiPeluang' => $this->isPotensiPeluang,
        ];
    }
}
