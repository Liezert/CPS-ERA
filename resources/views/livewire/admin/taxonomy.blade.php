@php
    $isCategory = $tab === 'kategori';
    $items = $isCategory ? $categories : $topics;
@endphp

<div class="max-w-4xl mx-auto space-y-6 pb-12">
    <header class="bg-white border border-neutral-200 rounded-md p-5 sm:p-6 shadow-2xs space-y-1">
        <h1 class="font-sans font-bold text-xl sm:text-2xl text-neutral-900 tracking-tight">Kategori &amp; Topik</h1>
        <p class="text-xs sm:text-sm text-neutral-600 max-w-[68ch]">
            Kelola kategori materi Learning dan topik dokumen Knowledge Repository dari satu halaman.
        </p>
    </header>

    @if (session('status'))
        <div class="p-3 bg-brand-tint border border-brand/20 rounded-md text-xs text-brand-dark">{{ session('status') }}</div>
    @endif

    @error('delete')
        <div class="p-3 bg-red-50 border border-red-200 rounded-md text-xs font-medium text-red-800" role="alert">{{ $message }}</div>
    @enderror

    <section class="bg-white border border-neutral-200 rounded-md shadow-2xs">
        {{-- Pilihan yang dikelola --}}
        <div class="flex border-b border-neutral-200 px-2" role="tablist">
            @if ($canManageCategories)
                <button type="button" role="tab" wire:click="$set('tab', 'kategori')" aria-selected="{{ $isCategory ? 'true' : 'false' }}"
                        class="px-4 py-3 text-xs font-bold border-b-2 -mb-px transition-colors {{ $isCategory ? 'border-brand text-brand-dark' : 'border-transparent text-neutral-600 hover:text-neutral-900' }}">
                    Kategori Learning
                </button>
            @endif
            @if ($canManageTopics)
                <button type="button" role="tab" wire:click="$set('tab', 'topik')" aria-selected="{{ $isCategory ? 'false' : 'true' }}"
                        class="px-4 py-3 text-xs font-bold border-b-2 -mb-px transition-colors {{ ! $isCategory ? 'border-brand text-brand-dark' : 'border-transparent text-neutral-600 hover:text-neutral-900' }}">
                    Topik Knowledge
                </button>
            @endif
        </div>

        <div class="p-5 space-y-5">
            {{-- Tambah baru --}}
            <form wire:submit="save" class="grid grid-cols-1 {{ $isCategory ? 'sm:grid-cols-[1fr_auto]' : 'sm:grid-cols-[1fr_1fr_auto]' }} gap-2 items-start">
                <div>
                    <label for="name" class="sr-only">Nama {{ $isCategory ? 'kategori' : 'topik' }}</label>
                    <input id="name" type="text" wire:model="name"
                           placeholder="{{ $isCategory ? 'Nama kategori baru, mis. Keselamatan Kerja' : 'Nama topik baru, mis. SOP Produksi' }}"
                           class="w-full px-3 py-2.5 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand" />
                    @error('name') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                @unless ($isCategory)
                    <div>
                        <label for="description" class="sr-only">Deskripsi topik</label>
                        <input id="description" type="text" wire:model="description" placeholder="Deskripsi singkat (opsional)"
                               class="w-full px-3 py-2.5 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand" />
                        @error('description') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endunless
                <button type="submit" class="px-4 py-2.5 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-semibold whitespace-nowrap">
                    + Tambah {{ $isCategory ? 'Kategori' : 'Topik' }}
                </button>
            </form>

            {{-- Daftar --}}
            <ul class="divide-y divide-neutral-100 border border-neutral-200 rounded-md">
                @forelse ($items as $item)
                    <li wire:key="{{ $tab }}-{{ $item->id }}" class="p-3">
                        @if ($editingId === $item->id)
                            <form wire:submit="update" class="grid grid-cols-1 {{ $isCategory ? 'sm:grid-cols-[1fr_auto]' : 'sm:grid-cols-[1fr_1fr_auto]' }} gap-2 items-start">
                                <div>
                                    <input type="text" wire:model="editName" aria-label="Nama"
                                           class="w-full px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand" />
                                    @error('editName') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                @unless ($isCategory)
                                    <input type="text" wire:model="editDescription" aria-label="Deskripsi"
                                           class="w-full px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand" />
                                @endunless
                                <div class="flex gap-2">
                                    <button type="submit" class="px-3 py-2 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-semibold">Simpan</button>
                                    <button type="button" wire:click="cancelEdit" class="px-3 py-2 border border-neutral-300 rounded-md text-xs text-neutral-700 hover:bg-neutral-50">Batal</button>
                                </div>
                            </form>
                        @else
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-neutral-900 truncate">{{ $item->name }}</p>
                                    <p class="text-xs text-neutral-500 truncate">
                                        {{ $isCategory ? $item->materials_count.' materi' : $item->documents_count.' dokumen' }}
                                        @if (! $isCategory && $item->description) &middot; {{ $item->description }} @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-3 shrink-0 text-xs font-semibold">
                                    <button type="button" wire:click="edit({{ $item->id }})" class="text-brand hover:text-brand-dark">Ubah</button>
                                    <button type="button" wire:click="delete({{ $item->id }})"
                                            wire:confirm="Hapus &quot;{{ $item->name }}&quot;?{{ ! $isCategory && $item->documents_count ? ' Dokumen yang memakai topik ini tetap ada, tanpa topik.' : '' }}"
                                            class="text-red-700 hover:text-red-900">Hapus</button>
                                </div>
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="p-6 text-center text-xs text-neutral-500">Belum ada {{ $isCategory ? 'kategori' : 'topik' }}.</li>
                @endforelse
            </ul>
        </div>
    </section>
</div>
