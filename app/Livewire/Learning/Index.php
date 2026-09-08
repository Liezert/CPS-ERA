<?php

namespace App\Livewire\Learning;

use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\UserLearningProgress;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Learning - CPS ERA')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: null)]
    public ?int $selectedCategoryId = null;

    #[Url(except: null)]
    public ?string $selectedType = null;

    #[Url(except: 'all')]
    public string $selectedProgressFilter = 'all'; // 'all', 'not_started', 'in_progress', 'completed'

    public string $viewMode = 'grid'; // 'grid' atau 'list'

    // State Modal Tambah Kategori (Admin & Quality)
    public bool $showCategoryModal = false;

    public string $newCategoryName = '';

    /**
     * Reset pagination setiap kali filter berubah.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedType(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedProgressFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Reset seluruh parameter filter ke kondisi awal.
     */
    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedCategoryId', 'selectedType', 'selectedProgressFilter']);
        $this->resetPage();
    }

    /**
     * Buka modal pembuatan kategori baru untuk Admin / Quality.
     */
    public function openCategoryModal(): void
    {
        if (! Auth::user()?->hasAnyRole(['admin', 'quality'])) {
            return;
        }

        $this->resetValidation();
        $this->newCategoryName = '';
        $this->showCategoryModal = true;
    }

    /**
     * Tutup modal kategori baru.
     */
    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
        $this->newCategoryName = '';
    }

    /**
     * Simpan kategori baru dari sisi Admin (DoD #3).
     */
    public function saveCategory(): void
    {
        $user = Auth::user();
        if (! $user?->hasAnyRole(['admin', 'quality'])) {
            abort(403, 'Hanya role Admin dan Quality yang berwenang mengelola kategori materi.');
        }

        $this->validate([
            'newCategoryName' => ['required', 'string', 'min:3', 'max:100', 'unique:learning_categories,name'],
        ], [
            'newCategoryName.required' => 'Nama kategori wajib diisi.',
            'newCategoryName.min' => 'Nama kategori minimal 3 karakter.',
            'newCategoryName.unique' => 'Nama kategori ini sudah terdaftar dalam sistem.',
        ]);

        LearningCategory::create([
            'name' => trim($this->newCategoryName),
            'created_by' => $user->id,
        ]);

        session()->flash('category_success', "Kategori '{$this->newCategoryName}' berhasil ditambahkan.");
        $this->closeCategoryModal();
    }

    public function render()
    {
        $userId = Auth::id();

        // 1. Kategori materi yang dikelola admin (DoD #3)
        $categories = LearningCategory::withCount('materials')->orderBy('name')->get();

        // 2. 7 Jenis Materi Resmi sesuai Data Contract §1
        $types = [
            'dokumen' => 'Dokumen',
            'video' => 'Video',
            'presentasi' => 'Presentasi',
            'artikel' => 'Artikel',
            'tutorial' => 'Tutorial',
            'link' => 'Tautan / Link',
            'file_pendukung' => 'File Pendukung',
        ];

        // 3. Query Materi Learning (Hanya yang published)
        $query = LearningMaterial::with(['category', 'creator', 'postTest'])
            ->published();

        // Filter Pencarian (Judul / Deskripsi)
        if (! empty(trim($this->search))) {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        // Filter Kategori Materi
        if (! empty($this->selectedCategoryId)) {
            $query->where('learning_category_id', $this->selectedCategoryId);
        }

        // Filter 7 Jenis Materi
        if (! empty($this->selectedType)) {
            $query->where('type', $this->selectedType);
        }

        // Filter Status Belajar Pengguna
        if ($this->selectedProgressFilter !== 'all' && $userId) {
            if ($this->selectedProgressFilter === 'completed') {
                $query->whereHas('progresses', function ($q) use ($userId) {
                    $q->where('user_id', $userId)->where('progress_percent', '>=', 100);
                });
            } elseif ($this->selectedProgressFilter === 'in_progress') {
                $query->whereHas('progresses', function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                        ->where('progress_percent', '>', 0)
                        ->where('progress_percent', '<', 100);
                });
            } elseif ($this->selectedProgressFilter === 'not_started') {
                $query->whereDoesntHave('progresses', function ($q) use ($userId) {
                    $q->where('user_id', $userId)->where('progress_percent', '>', 0);
                });
            }
        }

        $materials = $query->latest()->paginate(9);

        // Ambil data progress user aktif untuk materi-materi yang sedang ditampilkan
        $materialIds = $materials->pluck('id')->all();
        $userProgresses = $userId
            ? UserLearningProgress::where('user_id', $userId)
                ->whereIn('learning_material_id', $materialIds)
                ->get()
                ->keyBy('learning_material_id')
            : collect();

        // Ringkasan metrik belajar pengguna
        $totalCompletedCount = $userId
            ? UserLearningProgress::where('user_id', $userId)->where('progress_percent', '>=', 100)->count()
            : 0;

        $totalInProgressCount = $userId
            ? UserLearningProgress::where('user_id', $userId)->where('progress_percent', '>', 0)->where('progress_percent', '<', 100)->count()
            : 0;

        return view('livewire.learning.index', [
            'materials' => $materials,
            'categories' => $categories,
            'types' => $types,
            'userProgresses' => $userProgresses,
            'totalCompletedCount' => $totalCompletedCount,
            'totalInProgressCount' => $totalInProgressCount,
        ]);
    }
}
