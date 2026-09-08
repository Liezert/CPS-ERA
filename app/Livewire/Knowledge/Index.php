<?php

namespace App\Livewire\Knowledge;

use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\UserBookmark;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Knowledge Repository - CPS ERA')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: null)]
    public ?int $selectedDivisionId = null;

    #[Url(except: null)]
    public ?string $selectedType = null;

    #[Url(except: false)]
    public bool $onlyBookmarks = false;

    public string $viewMode = 'grid'; // 'grid' atau 'list'

    /**
     * Reset pagination setiap kali filter berubah (search/divisi/tipe/bookmark).
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedDivisionId(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedType(): void
    {
        $this->resetPage();
    }

    public function updatingOnlyBookmarks(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle filter hanya materi yang di-bookmark oleh user saat ini.
     */
    public function toggleOnlyBookmarks(): void
    {
        $this->onlyBookmarks = ! $this->onlyBookmarks;
        $this->resetPage();
    }

    /**
     * Reset seluruh parameter filter ke default.
     */
    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedDivisionId', 'selectedType', 'onlyBookmarks']);
        $this->resetPage();
    }

    /**
     * Toggle status simpan bookmark untuk user aktif.
     * Sesuai ERD relasi user_bookmarks (UUID, user_id, knowledge_document_id).
     */
    public function toggleBookmark(string $documentId): void
    {
        $userId = Auth::id();
        if (! $userId) {
            return;
        }

        $existing = UserBookmark::where('user_id', $userId)
            ->where('knowledge_document_id', $documentId)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            UserBookmark::create([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'knowledge_document_id' => $documentId,
            ]);
        }
    }

    public function render()
    {
        $userId = Auth::id();

        // 1. Ambil 13 Divisi Tetap sesuai Design System §8
        $divisions = Division::orderBy('id')->get();

        // 2. Daftar 6 Tipe Materi Resmi
        $types = [
            'dokumen' => 'Dokumen',
            'video' => 'Video',
            'presentasi' => 'Presentasi',
            'lesson_learned' => 'Lesson Learned',
            'sop' => 'SOP',
            'link' => 'Tautan / Link',
        ];

        // 3. Query Knowledge Documents
        $query = KnowledgeDocument::with(['division', 'creator'])
            ->published();

        // Filter pencarian reaktif (title atau description)
        if (! empty(trim($this->search))) {
            $query->where(function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        // Filter Divisi (13 divisi tetap)
        if (! empty($this->selectedDivisionId)) {
            $query->where('division_id', $this->selectedDivisionId);
        }

        // Filter Tipe Materi
        if (! empty($this->selectedType)) {
            $query->where('type', $this->selectedType);
        }

        // Filter Hanya Bookmark Pengguna
        if ($this->onlyBookmarks && $userId) {
            $query->whereHas('bookmarks', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $documents = $query->latest()->paginate(9);

        // Kumpulan ID dokumen yang di-bookmark oleh user saat ini
        $bookmarkedDocIds = $userId
            ? UserBookmark::where('user_id', $userId)->pluck('knowledge_document_id')->all()
            : [];

        // Hitung total bookmark user untuk badge counter
        $totalBookmarksCount = $userId
            ? UserBookmark::where('user_id', $userId)->count()
            : 0;

        return view('livewire.knowledge.index', [
            'documents' => $documents,
            'divisions' => $divisions,
            'types' => $types,
            'bookmarkedDocIds' => $bookmarkedDocIds,
            'totalBookmarksCount' => $totalBookmarksCount,
        ]);
    }
}
