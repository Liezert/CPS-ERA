<?php

namespace App\Livewire\Admin;

use App\Models\KnowledgeTopic;
use App\Models\LearningCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Satu halaman untuk HR mengelola Kategori Learning dan Topik Knowledge Repository, dengan
 * tampilan utama CPS ERA (keputusan owner 2026-09-27), menggantikan dua halaman terpisah di panel admin.
 */
#[Layout('layouts.app')]
#[Title('Kategori & Topik - CPS ERA')]
class Taxonomy extends Component
{
    #[Url]
    public string $tab = 'kategori'; // 'kategori' | 'topik'

    public string $name = '';

    public string $description = '';

    public ?int $editingId = null;

    public string $editName = '';

    public string $editDescription = '';

    public function mount(): void
    {
        abort_unless($this->canManageCategories() || $this->canManageTopics(), 403);

        $this->tab = $this->allowedTab($this->tab);
    }

    public function updatedTab(): void
    {
        $this->tab = $this->allowedTab($this->tab);
        $this->reset(['name', 'description', 'editingId', 'editName', 'editDescription']);
        $this->resetValidation();
    }

    public function save(): void
    {
        if ($this->tab === 'kategori') {
            Gate::authorize('create', LearningCategory::class);
            $this->validate(['name' => $this->nameRules('learning_categories', 100)], $this->messages());

            LearningCategory::create(['name' => trim($this->name), 'created_by' => Auth::id()]);
        } else {
            Gate::authorize('manage-knowledge-topics');
            $this->validate([
                'name' => $this->nameRules('knowledge_topics', 150),
                'description' => ['nullable', 'string', 'max:500'],
            ], $this->messages());

            KnowledgeTopic::create([
                'name' => trim($this->name),
                'description' => trim($this->description) ?: null,
                'created_by' => Auth::id(),
            ]);
        }

        session()->flash('status', ($this->tab === 'kategori' ? 'Kategori' : 'Topik')." \"{$this->name}\" berhasil ditambahkan.");
        $this->reset(['name', 'description']);
    }

    public function edit(int $id): void
    {
        $record = $this->findRecord($id);
        $this->editingId = $record->id;
        $this->editName = $record->name;
        $this->editDescription = (string) ($record->description ?? '');
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'editName', 'editDescription']);
        $this->resetValidation();
    }

    public function update(): void
    {
        $record = $this->findRecord((int) $this->editingId);
        $isCategory = $record instanceof LearningCategory;
        $isCategory ? Gate::authorize('update', $record) : Gate::authorize('manage-knowledge-topics');

        $this->validate([
            'editName' => $this->nameRules($record->getTable(), $isCategory ? 100 : 150, $record->id),
            'editDescription' => ['nullable', 'string', 'max:500'],
        ], $this->messages());

        $record->update($isCategory
            ? ['name' => trim($this->editName)]
            : ['name' => trim($this->editName), 'description' => trim($this->editDescription) ?: null]);

        session()->flash('status', 'Perubahan tersimpan.');
        $this->cancelEdit();
    }

    public function delete(int $id): void
    {
        $record = $this->findRecord($id);

        if ($record instanceof LearningCategory) {
            Gate::authorize('delete', $record);

            // Materi terhapus ikut (cascade) bila kategori dihapus, jadi kategori yang masih dipakai ditolak.
            $materialCount = $record->materials()->count();
            if ($materialCount > 0) {
                $this->addError('delete', "Kategori \"{$record->name}\" masih dipakai {$materialCount} materi. Pindahkan materinya ke kategori lain sebelum menghapus.");

                return;
            }
        } else {
            // Dokumen yang memakai topik ini tetap ada, hanya kehilangan topiknya.
            Gate::authorize('manage-knowledge-topics');
        }

        $name = $record->name;
        $record->delete();
        session()->flash('status', "\"{$name}\" dihapus.");
    }

    public function render()
    {
        return view('livewire.admin.taxonomy', [
            'canManageCategories' => $this->canManageCategories(),
            'canManageTopics' => $this->canManageTopics(),
            'categories' => $this->tab === 'kategori' ? LearningCategory::withCount('materials')->orderBy('name')->get() : collect(),
            'topics' => $this->tab === 'topik' ? KnowledgeTopic::withCount('documents')->orderBy('name')->get() : collect(),
        ]);
    }

    private function findRecord(int $id): LearningCategory|KnowledgeTopic
    {
        return $this->tab === 'kategori' ? LearningCategory::findOrFail($id) : KnowledgeTopic::findOrFail($id);
    }

    /**
     * @return array<int, mixed>
     */
    private function nameRules(string $table, int $max, ?int $ignoreId = null): array
    {
        return ['required', 'string', 'min:3', "max:{$max}", Rule::unique($table, 'name')->ignore($ignoreId)];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.min' => 'Nama minimal 3 karakter.',
            'name.unique' => 'Nama ini sudah terdaftar.',
            'editName.required' => 'Nama wajib diisi.',
            'editName.min' => 'Nama minimal 3 karakter.',
            'editName.unique' => 'Nama ini sudah terdaftar.',
        ];
    }

    private function canManageCategories(): bool
    {
        return Gate::allows('create', LearningCategory::class);
    }

    private function canManageTopics(): bool
    {
        return Gate::allows('manage-knowledge-topics');
    }

    private function allowedTab(string $tab): string
    {
        return match (true) {
            $tab === 'topik' && $this->canManageTopics(), ! $this->canManageCategories() => 'topik',
            default => 'kategori',
        };
    }
}
