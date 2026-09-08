<?php

namespace App\Livewire\Ba;

use App\Models\BaIncident;
use App\Models\Division;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('BA & Lesson Learned - CPS ERA')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: null)]
    public ?int $selectedDivisionId = null;

    #[Url(except: null)]
    public ?string $selectedStatus = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedDivisionId(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedDivisionId', 'selectedStatus']);
        $this->resetPage();
    }

    public function render()
    {
        $divisions = Division::orderBy('id')->get();

        $query = BaIncident::with(['division', 'creator', 'reviewer']);

        if (! empty(trim($this->search))) {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('nomor_ba', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if (! empty($this->selectedDivisionId)) {
            $query->where('division_id', $this->selectedDivisionId);
        }

        if (! empty($this->selectedStatus)) {
            $query->where('status', $this->selectedStatus);
        }

        $incidents = $query->latest()->paginate(10);

        return view('livewire.ba.index', [
            'incidents' => $incidents,
            'divisions' => $divisions,
        ]);
    }
}
