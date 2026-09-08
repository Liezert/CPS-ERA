<?php

namespace App\Livewire\Ba;

use App\Models\Division;
use App\Services\BaIncidentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Buat Laporan BA Baru - CPS ERA')]
class Create extends Component
{
    use WithFileUploads;

    public string $nomorBaPreview = '';

    public ?int $divisionId = null;

    public string $title = '';

    public string $description = '';

    public $fileBa = null;

    public $fileFtk = null;

    public function mount(BaIncidentService $service): void
    {
        $user = Auth::user();
        $this->divisionId = $user?->division_id;

        // Auto-generate nomor BA format BA-YYYY-NNNN untuk ditampilkan read-only
        $this->nomorBaPreview = $service->generateNomorBa();
    }

    protected function rules(): array
    {
        // TODO: Menunggu keputusan PRD §5.3 (Poin 6: Batas ukuran file upload) - batas interim 10MB (10240 KB)
        return [
            'divisionId' => ['required', 'exists:divisions,id'],
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'fileBa' => ['required', 'file', 'extensions:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'fileFtk' => ['required', 'file', 'extensions:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ];
    }

    protected function messages(): array
    {
        return [
            'divisionId.required' => 'Divisi wajib dipilih dari 13 opsi resmi.',
            'title.required' => 'Judul insiden wajib diisi.',
            'description.required' => 'Deskripsi kronologi kejadian wajib diisi.',
            'fileBa.required' => 'Berkas Dokumen BA wajib diunggah.',
            'fileBa.max' => 'Ukuran berkas BA maksimal 10MB.',
            'fileFtk.required' => 'Berkas Formulir FTK wajib diunggah.',
            'fileFtk.max' => 'Ukuran berkas FTK maksimal 10MB.',
        ];
    }

    public function save(BaIncidentService $service)
    {
        $this->validate();

        $user = Auth::user();

        $incident = $service->create(
            [
                'division_id' => $this->divisionId,
                'title' => $this->title,
                'description' => $this->description,
            ],
            $user,
            $this->fileBa,
            $this->fileFtk
        );

        session()->flash('success', "Laporan Berita Acara {$incident->nomor_ba} berhasil diterbitkan dan siap diverifikasi oleh supervisor divisi.");

        return $this->redirect(route('ba.index'), navigate: true);
    }

    public function render()
    {
        // 13 Divisi Tetap sesuai Design System §8
        $divisions = Division::orderBy('id')->get();

        return view('livewire.ba.create', [
            'divisions' => $divisions,
        ]);
    }
}
