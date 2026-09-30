<?php

namespace App\Livewire\Video;

use App\Models\LearningCategory;
use App\Services\VideoApprovalService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Unggah video kontribusi (keputusan owner 2026-09-27): karyawan/supervisor berbagi video,
 * HR yang menyetujui, lalu video tampil di Learning untuk karyawan lain.
 */
#[Layout('layouts.app')]
#[Title('Unggah Video Kontribusi - CPS ERA')]
class Create extends Component
{
    use WithFileUploads;

    public string $title = '';

    public string $description = '';

    public ?int $learningCategoryId = null;

    public string $videoMethod = 'file'; // 'file' | 'link'

    public $videoFile = null;

    public string $videoExternalLink = '';

    public function submit(VideoApprovalService $service)
    {
        $isFile = $this->videoMethod === 'file';

        $this->validate([
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'learningCategoryId' => ['nullable', 'integer', 'exists:learning_categories,id'],
            'videoFile' => $isFile ? ['required', 'file', 'mimes:mp4,mov,webm,mkv', 'max:102400'] : ['nullable'],
            // Sesuai label form: hanya Google Drive / OneDrive (bisa diputar di Learning & dikelola perusahaan).
            'videoExternalLink' => $isFile ? ['nullable'] : ['required', 'url', 'max:255', 'regex:#^https://(drive\.google\.com|docs\.google\.com|onedrive\.live\.com|1drv\.ms|[a-z0-9-]+\.sharepoint\.com)/#i'],
        ], [
            'videoExternalLink.regex' => 'Tautan harus dari Google Drive atau OneDrive.',
            'title.required' => 'Judul video wajib diisi.',
            'videoFile.required' => 'Pilih berkas video yang akan diunggah.',
            'videoFile.mimes' => 'Format video harus mp4, mov, webm, atau mkv.',
            'videoFile.max' => 'Ukuran video maksimal 100MB.',
            'videoExternalLink.required' => 'Tautan video wajib diisi.',
            'videoExternalLink.url' => 'Tautan video harus berupa URL valid (contoh: https://drive.google.com/...).',
        ]);

        try {
            $video = $service->submit(Auth::user(), [
                'title' => trim($this->title),
                'description' => trim($this->description) ?: null,
                'learning_category_id' => $this->learningCategoryId,
                'video_file' => $isFile ? $this->videoFile : null,
                'video_external_link' => $isFile ? null : trim($this->videoExternalLink),
            ]);
        } catch (DomainException $exception) {
            $this->addError('video', $exception->getMessage());

            return;
        }

        session()->flash('status', 'Video berhasil dikirim dan menunggu review tim HR.');

        return $this->redirect(route('videos.show', $video), navigate: true);
    }

    public function render()
    {
        return view('livewire.video.create', [
            'categories' => LearningCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
