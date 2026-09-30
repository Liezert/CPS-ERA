{{-- dirty: ada perubahan yang belum disimpan (ketikan atau aksi kartu selain Simpan) --}}
<div class="max-w-3xl mx-auto space-y-4 pb-24"
     data-form-draft="title,description,pointsReward,questions"
     x-data="{ dirty: false }"
     x-on:input="dirty = true"
     x-on:click="if ($event.target.closest('[wire\\:click]:not([data-save])')) dirty = true">
    @if (session('status'))
        <div class="p-3 bg-brand-tint border border-brand/20 rounded-md text-xs font-medium text-brand-dark" role="status">{{ session('status') }}</div>
    @endif

    <nav class="flex items-center gap-2 text-xs text-neutral-600" aria-label="Breadcrumb">
        <a href="{{ route('learning.show', $material) }}" class="hover:text-brand truncate">{{ $material->title }}</a>
        <span aria-hidden="true">&rsaquo;</span>
        <span class="font-semibold text-neutral-900">Kelola Post-Test</span>
    </nav>

    {{-- Kartu judul (seperti kartu judul Google Form) --}}
    <section class="bg-white border border-neutral-200 border-t-8 border-t-brand rounded-lg p-5 sm:p-6 space-y-3 shadow-2xs">
        <label for="pt-title" class="sr-only">Judul post-test</label>
        <input id="pt-title" type="text" wire:model="title" placeholder="Judul post-test"
               class="w-full text-2xl font-semibold text-neutral-900 border-0 border-b border-transparent px-0 py-1 focus:ring-0 focus:border-brand hover:border-neutral-200" />
        @error('title') <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror

        <label for="pt-description" class="sr-only">Petunjuk pengerjaan</label>
        <textarea id="pt-description" wire:model="description" rows="2" placeholder="Petunjuk pengerjaan (opsional)"
                  class="w-full text-sm text-neutral-700 border-0 border-b border-transparent px-0 py-1 resize-none focus:ring-0 focus:border-brand hover:border-neutral-200"></textarea>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 pt-1 text-xs text-neutral-600">
            <label class="inline-flex items-center gap-2">
                <span>XP untuk yang lulus</span>
                <input type="number" min="0" max="1000" wire:model="pointsReward"
                       class="w-20 h-8 px-2 text-xs border border-neutral-300 rounded-md focus:ring-2 focus:ring-brand/20 focus:border-brand" />
            </label>
            <span>Lulus = semua jawaban benar. Klik lingkaran di kiri opsi untuk menandai jawaban benar.</span>
        </div>
        @error('pointsReward') <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror
    </section>

    @error('questions') <div class="p-3 bg-red-50 border border-red-200 rounded-md text-xs text-red-800" role="alert">{{ $message }}</div> @enderror

    {{-- Satu kartu per soal; garis kiri hijau menandai kartu yang sedang diisi --}}
    @foreach ($questions as $q => $question)
        <section wire:key="question-{{ $question['id'] }}"
                 class="group bg-white border border-neutral-200 border-l-4 border-l-transparent focus-within:border-l-brand rounded-lg p-5 sm:p-6 space-y-4 shadow-2xs transition-colors"
                 aria-label="Soal {{ $q + 1 }}">
            <div class="flex flex-col sm:flex-row gap-3">
                {{-- Editor teks soal: tebal/miring/garis bawah/tautan. HTML disaring server saat simpan & tampil. --}}
                <div class="flex-1 min-w-0"
                     x-data="{
                        sync() {
                            this.$refs.store.value = this.$refs.editor.innerHTML;
                            this.$refs.store.dispatchEvent(new Event('input'));
                        },
                        format(command) {
                            this.$refs.editor.focus();
                            document.execCommand(command);
                            this.sync();
                        },
                        link() {
                            const selection = window.getSelection();
                            const range = selection.rangeCount && this.$refs.editor.contains(selection.anchorNode) ? selection.getRangeAt(0) : null;
                            let url = (window.prompt('Masukkan tautan (contoh: https://cps.co.id)') || '').trim();
                            if (! url) return;
                            if (! /^(https?:\/\/|mailto:)/i.test(url)) url = 'https://' + url;
                            this.$refs.editor.focus();
                            if (range && ! range.collapsed) {
                                selection.removeAllRanges();
                                selection.addRange(range);
                                document.execCommand('createLink', false, url);
                            } else {
                                const anchor = document.createElement('a');
                                anchor.href = url;
                                anchor.textContent = url;
                                range ? range.insertNode(anchor) : this.$refs.editor.appendChild(anchor);
                            }
                            this.sync();
                        }
                     }">
                    <div wire:ignore>
                        <div x-ref="editor" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Pertanyaan {{ $q + 1 }}"
                             data-placeholder="Pertanyaan"
                             x-init="$el.innerHTML = @js($question['text'])"
                             x-on:input="sync()"
                             x-on:keydown.enter.prevent="document.execCommand('insertLineBreak'); sync()"
                             class="min-h-[3rem] w-full text-sm text-neutral-900 bg-neutral-50 border-b-2 border-neutral-200 rounded-t-md px-3 py-2.5 focus:outline-none focus:border-brand [&_a]:text-brand [&_a]:underline empty:before:content-[attr(data-placeholder)] empty:before:text-neutral-400"></div>
                    </div>
                    <input type="hidden" x-ref="store" wire:model="questions.{{ $q }}.text">

                    {{-- Toolbar format: tampil saat kartu sedang diisi, seperti Google Form --}}
                    <div class="hidden group-focus-within:flex items-center gap-0.5 pt-1.5" role="toolbar" aria-label="Format teks pertanyaan">
                        @foreach ([
                            ['bold', 'Tebal (Ctrl+B)', '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h6a3.75 3.75 0 010 7.5h-6zm0 7.5h7.5a3.75 3.75 0 010 7.5h-7.5z" />'],
                            ['italic', 'Miring (Ctrl+I)', '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 3.75h7.5M6 20.25h7.5M14.25 3.75L9.75 20.25" />'],
                            ['underline', 'Garis bawah (Ctrl+U)', '<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 3.75v6.75a5.25 5.25 0 01-10.5 0V3.75M5.25 20.25h13.5" />'],
                        ] as [$command, $label, $path])
                            <button type="button" x-on:mousedown.prevent x-on:click="format('{{ $command }}')" title="{{ $label }}" aria-label="{{ $label }}"
                                    class="p-1.5 rounded-md text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true">{!! $path !!}</svg>
                            </button>
                        @endforeach
                        <span class="w-px h-4 bg-neutral-200 mx-1" aria-hidden="true"></span>
                        <button type="button" x-on:mousedown.prevent x-on:click="link()" title="Sisipkan tautan" aria-label="Sisipkan tautan"
                                class="p-1.5 rounded-md text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                        </button>
                    </div>
                    @error("questions.$q.text") <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Jenis soal: dua tombol ber-ikon SVG (bukan emoji) --}}
                <div class="inline-flex self-start shrink-0 rounded-md border border-neutral-300 p-0.5 bg-white" role="radiogroup" aria-label="Jenis soal {{ $q + 1 }}">
                    <button type="button" role="radio" aria-checked="{{ $question['multiple'] ? 'false' : 'true' }}" wire:click="setMultiple({{ $q }}, false)"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded text-xs font-medium {{ $question['multiple'] ? 'text-neutral-600 hover:bg-neutral-50' : 'bg-brand-tint text-brand-dark' }}">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.25" stroke="currentColor" stroke-width="2" /><circle cx="12" cy="12" r="4" fill="currentColor" /></svg>
                        Pilihan ganda
                    </button>
                    <button type="button" role="radio" aria-checked="{{ $question['multiple'] ? 'true' : 'false' }}" wire:click="setMultiple({{ $q }}, true)"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded text-xs font-medium {{ $question['multiple'] ? 'bg-brand-tint text-brand-dark' : 'text-neutral-600 hover:bg-neutral-50' }}">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.75" y="3.75" width="16.5" height="16.5" rx="3" stroke="currentColor" stroke-width="2" /><path d="M8 12.5l2.75 2.75L16 9.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        Kotak centang
                    </button>
                </div>
            </div>

            <ul class="space-y-1.5">
                @foreach ($question['options'] as $o => $option)
                    <li wire:key="question-{{ $q }}-option-{{ $o }}-{{ count($question['options']) }}" class="flex items-center gap-3 group">
                        <button type="button" wire:click="toggleCorrect({{ $q }}, {{ $o }})"
                                aria-pressed="{{ $option['correct'] ? 'true' : 'false' }}"
                                aria-label="Tandai opsi {{ $o + 1 }} sebagai jawaban benar"
                                title="{{ $option['correct'] ? 'Jawaban benar' : 'Tandai sebagai jawaban benar' }}"
                                class="w-5 h-5 shrink-0 border-2 flex items-center justify-center transition-colors {{ $question['multiple'] ? 'rounded-[3px]' : 'rounded-full' }} {{ $option['correct'] ? 'bg-brand border-brand text-white' : 'border-neutral-400 hover:border-brand' }}">
                            @if ($option['correct'])
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            @endif
                        </button>
                        <div class="flex-1 min-w-0">
                            <label for="q-{{ $q }}-o-{{ $o }}" class="sr-only">Opsi {{ $o + 1 }}</label>
                            <input id="q-{{ $q }}-o-{{ $o }}" type="text" wire:model="questions.{{ $q }}.options.{{ $o }}.text" placeholder="Opsi {{ $o + 1 }}"
                                   class="w-full text-sm border-0 border-b border-transparent px-0 py-1.5 focus:ring-0 focus:border-brand hover:border-neutral-200 {{ $option['correct'] ? 'font-semibold text-brand-dark' : 'text-neutral-800' }}" />
                            @error("questions.$q.options.$o.text") <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror
                        </div>
                        @if ($option['correct'])
                            <span class="hidden sm:inline text-xs font-medium text-brand-dark shrink-0">Jawaban benar</span>
                        @endif
                        @if (count($question['options']) > 2)
                            <button type="button" wire:click="removeOption({{ $q }}, {{ $o }})" aria-label="Hapus opsi {{ $o + 1 }}"
                                    class="p-1.5 rounded-md text-neutral-400 hover:text-neutral-800 hover:bg-neutral-100 shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        @endif
                    </li>
                @endforeach
                <li class="flex items-center gap-3">
                    <span class="w-5 h-5 shrink-0 border-2 border-neutral-300 {{ $question['multiple'] ? 'rounded-[3px]' : 'rounded-full' }}" aria-hidden="true"></span>
                    <button type="button" wire:click="addOption({{ $q }})" class="text-sm text-neutral-500 hover:text-brand py-1.5">Tambahkan opsi</button>
                </li>
            </ul>
            @error("questions.$q.options") <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror
            @error("questions.$q.correct") <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror

            {{-- Footer kartu: geser, duplikat, hapus --}}
            <div class="flex items-center justify-end gap-1 pt-3 border-t border-neutral-200">
                <button type="button" wire:click="moveQuestion({{ $q }}, -1)" @disabled($q === 0) aria-label="Geser soal {{ $q + 1 }} ke atas" title="Geser ke atas"
                        class="p-2 rounded-md text-neutral-600 hover:bg-neutral-100 disabled:opacity-30 disabled:hover:bg-transparent">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" wire:click="moveQuestion({{ $q }}, 1)" @disabled($loop->last) aria-label="Geser soal {{ $q + 1 }} ke bawah" title="Geser ke bawah"
                        class="p-2 rounded-md text-neutral-600 hover:bg-neutral-100 disabled:opacity-30 disabled:hover:bg-transparent">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <span class="w-px h-6 bg-neutral-200 mx-1" aria-hidden="true"></span>
                <button type="button" wire:click="duplicateQuestion({{ $q }})" aria-label="Duplikat soal {{ $q + 1 }}" title="Duplikat"
                        class="p-2 rounded-md text-neutral-600 hover:bg-neutral-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" /></svg>
                </button>
                <button type="button" wire:click="removeQuestion({{ $q }})" aria-label="Hapus soal {{ $q + 1 }}" title="Hapus"
                        class="p-2 rounded-md text-neutral-600 hover:bg-red-50 hover:text-red-700">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.94-2.164-2.201-2.201a51.964 51.964 0 00-3.32 0c-1.26.037-2.2 1.022-2.2 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
            </div>
        </section>
    @endforeach

    <button type="button" wire:click="addQuestion"
            class="w-full flex items-center justify-center gap-2 py-3 border-2 border-dashed border-neutral-300 rounded-lg text-sm font-medium text-neutral-600 hover:border-brand hover:text-brand bg-white/60 transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Tambah pertanyaan
    </button>

    {{-- Notifikasi hapus ala Google Form: soal langsung terhapus, bisa diurungkan --}}
    @if ($lastRemoved)
        <div wire:key="removed-toast-{{ $removedCount }}" x-data="{ show: true }" x-init="setTimeout(() => show = false, 8000)" x-show="show" x-transition
             role="status"
             class="fixed left-4 tablet:left-24 desktop:left-72 bottom-32 tablet:bottom-20 z-30 flex items-center gap-6 px-4 py-3 bg-neutral-900 text-white rounded-md shadow-lg text-sm">
            <span>Soal dihapus</span>
            <button type="button" wire:click="undoRemove" x-on:click="show = false" class="font-semibold uppercase tracking-wide text-amber-300 hover:text-amber-200">Urungkan</button>
        </div>
    @endif

    {{-- Bar simpan menempel di bawah layar --}}
    <div class="fixed bottom-16 tablet:bottom-0 inset-x-0 tablet:left-20 desktop:left-64 z-20 bg-white/95 backdrop-blur border-t border-neutral-200">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <span class="text-xs text-neutral-600">
                {{ count($questions) }} soal
                <span x-show="dirty" x-cloak class="font-semibold text-amber-800">· Belum disimpan</span>
            </span>
            <div class="flex items-center gap-2">
                <a href="{{ route('learning.show', $material) }}" wire:navigate
                   class="px-4 py-2 border border-neutral-300 bg-white rounded-md text-sm font-medium text-neutral-700 hover:bg-neutral-50">Kembali</a>
                <button type="button" wire:click="save" data-save wire:loading.attr="disabled" wire:target="save"
                        class="px-5 py-2 bg-brand hover:bg-brand-dark text-white rounded-md text-sm font-semibold disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">Simpan Post-Test</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
</div>
