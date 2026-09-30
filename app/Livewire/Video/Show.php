<?php

namespace App\Livewire\Video;

use App\Models\Video;
use App\Services\VideoApprovalService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Detail video kontribusi. Tim HR menyetujui/menolak langsung di halaman ini.
 */
#[Layout('layouts.app')]
#[Title('Detail Video Kontribusi - CPS ERA')]
class Show extends Component
{
    public Video $video;

    public string $catatanHr = '';

    public string $alasanPenolakan = '';

    public function mount(Video $video): void
    {
        Gate::authorize('view', $video);

        $this->video = $video->load(['creator', 'division', 'learningCategory', 'hrReviewer', 'learningMaterial']);
    }

    public function getCanReviewProperty(): bool
    {
        return (bool) Auth::user()?->can('review', $this->video);
    }

    public function approve(VideoApprovalService $service): void
    {
        abort_unless($this->canReview, 403);

        $this->validate(['catatanHr' => ['nullable', 'string', 'max:1000']]);

        try {
            $service->approve($this->video, Auth::user(), trim($this->catatanHr) ?: null);
        } catch (DomainException $exception) {
            $this->addError('review', $exception->getMessage());

            return;
        }

        session()->flash('status', 'Video disetujui dan sudah tampil di Learning. Pengunggah mendapat 1 Poin CPS ERA (selama belum mencapai batas tahunan).');

        $this->redirect(route('videos.show', $this->video), navigate: true);
    }

    public function reject(VideoApprovalService $service): void
    {
        abort_unless($this->canReview, 403);

        $this->validate(
            ['alasanPenolakan' => ['required', 'string', 'min:5', 'max:2000']],
            [
                'alasanPenolakan.required' => 'Alasan penolakan wajib diisi.',
                'alasanPenolakan.min' => 'Alasan penolakan minimal 5 karakter.',
            ],
        );

        try {
            $service->reject($this->video, Auth::user(), $this->alasanPenolakan);
        } catch (DomainException $exception) {
            $this->addError('review', $exception->getMessage());

            return;
        }

        session()->flash('status', 'Video ditolak. Alasan penolakan ditampilkan ke pengunggah.');

        $this->redirect(route('videos.show', $this->video), navigate: true);
    }

    public function render()
    {
        return view('livewire.video.show');
    }
}
