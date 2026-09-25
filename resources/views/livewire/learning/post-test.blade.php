<div class="max-w-4xl mx-auto space-y-6">
    {{-- Breadcrumb Navigasi --}}
    <nav class="flex items-center gap-2 text-xs text-neutral-500 font-sans">
        <a href="{{ route('learning.index') }}" class="hover:text-brand transition">Learning</a>
        <span>/</span>
        @if ($material->category)
            <span class="text-neutral-500">{{ $material->category->name }}</span>
            <span>/</span>
        @endif
        <a href="{{ route('learning.show', $material) }}" class="hover:text-brand transition truncate max-w-[200px] sm:max-w-xs" title="{{ $material->title }}">
            {{ $material->title }}
        </a>
        <span>/</span>
        <span class="text-neutral-900 font-medium">Evaluasi Post-Test</span>
    </nav>

    {{-- Header Kartu Post-Test --}}
    <div class="bg-white border border-neutral-200 rounded-lg p-5 sm:p-6 space-y-3 shadow-none">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                {{-- Badge Tipe (Stempel 2px) --}}
                <span class="inline-flex items-center gap-1.5 text-xs uppercase tracking-wider text-neutral-600 border border-neutral-200 px-2 py-0.5 rounded-[2px] font-sans">
                    <x-layout.nav-icon name="academic" class="w-3.5 h-3.5 text-neutral-500" />
                    Evaluasi Post-Test (Syarat KPI)
                </span>

                {{-- Status Hasil Percobaan Sebelumnya --}}
                @if ($latestAttempt)
                    <span class="inline-flex items-center gap-1 text-xs border {{ $latestAttempt->passed ? 'border-brand text-brand-dark bg-brand-tint' : 'border-neutral-200 text-neutral-600' }} px-2 py-0.5 rounded-[2px] font-sans">
                        @if ($latestAttempt->passed)
                            <svg class="w-3 h-3 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Pernah Lulus (Skor 100%)
                        @else
                            Percobaan Terakhir (Skor {{ $latestAttempt->score }}%)
                        @endif
                    </span>
                @endif
            </div>

            @if ($material->xp_reward && $material->xp_reward > 0)
                <span class="font-mono text-xs font-semibold text-brand-dark bg-brand-tint border border-brand/30 px-2.5 py-1 rounded-[2px]">
                    +{{ $material->xp_reward }} XP Reward
                </span>
            @endif
        </div>

        <h1 class="text-xl sm:text-2xl font-semibold text-neutral-900 tracking-tight font-sans">
            {{ $quiz->title }}
        </h1>

        <div class="flex flex-wrap items-center gap-3 text-xs text-neutral-500 pt-1 font-sans">
            <span>Materi: <strong class="text-neutral-700 font-medium">{{ $material->title }}</strong></span>
            <span>•</span>
            <span>{{ $totalQuestions }} Pertanyaan Evaluasi</span>
            <span>•</span>
            <span>Standar Kelulusan KPI: <strong class="text-neutral-900 font-medium font-mono">100% (Wajib Sempurna)</strong></span>
            <span>•</span>
            <span class="text-brand font-medium">{{ $answeredCount }}/{{ $totalQuestions }} Terjawab</span>
        </div>

        @if ($quiz->description)
            <div class="pt-2 text-xs text-neutral-600 leading-relaxed font-sans">
                {{ $quiz->description }}
            </div>
        @endif

        {{-- Petunjuk Post-Test PRD v2.0 §3.3 --}}
        <div class="pt-2 border-t border-neutral-100 flex flex-wrap items-center justify-between text-[11px] text-neutral-500 font-sans gap-2">
            <span>Aturan: Percobaan tidak dibatasi (unlimited attempt). Evaluasi hanya diakui untuk KPI jika mendapat skor 100%.</span>
            <span class="text-neutral-400 italic">Kunci jawaban disembunyikan demi integritas evaluasi</span>
        </div>
    </div>

    {{-- Daftar Pertanyaan dan Opsi Jawaban --}}
    <div class="space-y-4">
        @foreach ($quiz->questions as $index => $question)
            @php
                $userAnswer = $userAnswers[$question->id] ?? null;
                $isMulti = (bool) $question->allow_multiple_answers;
            @endphp
            <div wire:key="post-test-q-{{ $question->id }}"
                 class="bg-white border border-neutral-200 rounded-lg p-5 space-y-4 transition shadow-none">

                {{-- Header Soal --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-mono uppercase tracking-wider text-neutral-400 block">
                                Pertanyaan {{ $index + 1 }} dari {{ $totalQuestions }}
                            </span>
                            @if ($isMulti)
                                <span class="text-[10px] font-mono px-1.5 py-0.2 bg-neutral-100 text-neutral-600 border border-neutral-200 rounded-[2px]">
                                    Pilihan Ganda (Pilih Semua Jawaban Benar)
                                </span>
                            @else
                                <span class="text-[10px] font-mono px-1.5 py-0.2 bg-neutral-100 text-neutral-600 border border-neutral-200 rounded-[2px]">
                                    Pilihan Tunggal
                                </span>
                            @endif
                        </div>
                        <h3 class="text-sm font-medium text-neutral-900 leading-snug font-sans">
                            {{ $question->question_text }}
                        </h3>
                    </div>
                </div>

                {{-- Daftar Opsi Jawaban --}}
                <div class="space-y-2 pt-1">
                    @foreach ($question->options as $optIndex => $option)
                        @php
                            $isSelected = $isMulti
                                ? (is_array($userAnswer) && in_array((string) $option->id, array_map('strval', $userAnswer), true))
                                : ((string) $userAnswer === (string) $option->id);

                            $optionClasses = $isSelected
                                ? 'border-brand bg-brand-tint/20 text-brand-dark font-medium'
                                : 'border-neutral-200 bg-white text-neutral-800 hover:border-neutral-300 hover:bg-neutral-50/50';
                        @endphp

                        <button type="button"
                                wire:key="opt-btn-{{ $option->id }}"
                                @if ($isMulti)
                                    wire:click="toggleOption('{{ $question->id }}', '{{ $option->id }}')"
                                @else
                                    wire:click="selectOption('{{ $question->id }}', '{{ $option->id }}')"
                                @endif
                                {{ $isSubmitted ? 'disabled' : '' }}
                                class="w-full text-left p-3.5 border rounded-lg text-xs leading-relaxed transition flex items-center justify-between gap-3 {{ $optionClasses }} focus:outline-none focus:ring-1 focus:ring-brand font-sans disabled:cursor-default">

                            <div class="flex items-center gap-3">
                                {{-- Bulatan Opsi (Radio) atau Kotak (Checkbox) --}}
                                @if ($isMulti)
                                    <div class="w-4 h-4 rounded-[3px] border flex items-center justify-center text-[10px] font-mono shrink-0
                                        {{ $isSelected ? 'border-brand bg-brand text-white' : 'border-neutral-300 bg-white' }}">
                                        @if ($isSelected)
                                            ✓
                                        @endif
                                    </div>
                                @else
                                    <div class="w-5 h-5 rounded-full border flex items-center justify-center text-[10px] font-mono shrink-0
                                        {{ $isSelected ? 'border-brand bg-brand text-white' : 'border-neutral-300 text-neutral-500 bg-white' }}">
                                        @if ($isSelected)
                                            •
                                        @else
                                            {{ chr(65 + $optIndex) }}
                                        @endif
                                    </div>
                                @endif

                                <span class="font-sans">{{ $option->option_text }}</span>
                            </div>

                            @if ($isSelected)
                                <span class="shrink-0 text-[10px] font-mono text-brand-dark">Pilihan Anda</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Panel Hasil Evaluasi Akhir (Muncul Setelah Submit) --}}
    @if ($isSubmitted)
        <div class="bg-white border border-neutral-200 rounded-lg p-6 space-y-4 shadow-none">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-neutral-200">
                <div>
                    <span class="text-xs uppercase tracking-wider text-neutral-400 font-mono">Hasil Evaluasi Post-Test</span>
                    <h2 class="text-xl font-semibold text-neutral-900 mt-0.5 font-sans">
                        @if ($passed)
                            Evaluasi Berhasil Diselesaikan (Lulus 100%)
                        @else
                            Belum Memenuhi Syarat Kelulusan KPI (Wajib 100%)
                        @endif
                    </h2>
                </div>

                {{-- Nilai Akhir dalam font Mono (Design System §3) --}}
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-semibold font-mono {{ $passed ? 'text-brand' : 'text-neutral-900' }}">
                        {{ $score }}%
                    </span>
                    <span class="text-xs text-neutral-500 font-mono">/ 100%</span>
                </div>
            </div>

            {{-- Ringkasan Hasil --}}
            <div class="text-xs text-neutral-600 space-y-2 font-sans">
                @if ($passed)
                    <div class="p-3 bg-brand-tint border border-brand/30 rounded-md text-brand-dark flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Selamat! Skor Anda 100%. Materi ini dihitung ke progres KPI Contribution periode ini.</span>
                    </div>
                @else
                    <div class="p-3 bg-neutral-50 border border-neutral-200 rounded-md text-neutral-700">
                        Skor Anda adalah <strong>{{ $score }}%</strong>. Untuk memenuhi syarat kontribusi KPI, evaluasi post-test mensyaratkan skor tepat <strong>100%</strong>. Sesuai PRD v2.0, Anda dapat mencoba kembali tanpa batas hingga menguasai seluruh materi.
                    </div>
                @endif
            </div>

            {{-- Tombol Aksi Bawah --}}
            <div class="pt-2 flex flex-wrap items-center justify-between gap-3 font-sans">
                <a href="{{ route('learning.show', $material) }}"
                   class="px-4 py-2 text-xs font-medium border border-neutral-200 rounded-lg text-neutral-700 hover:bg-neutral-50 transition">
                    Kembali ke Materi
                </a>

                @if (! $passed)
                    <button type="button"
                            wire:click="resetPostTest"
                            class="px-4 py-2 text-xs font-medium bg-brand text-white rounded-lg hover:bg-brand-dark transition">
                        Coba Ulang Post-Test
                    </button>
                @endif
            </div>
        </div>
    @else
        {{-- Kontrol Aksi Submit Pengerjaan --}}
        <div class="bg-white border border-neutral-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-none font-sans">
            <div class="text-xs text-neutral-500">
                @if ($answeredCount < $totalQuestions)
                    <span>Masih ada {{ $totalQuestions - $answeredCount }} pertanyaan yang belum dijawab.</span>
                @else
                    <span class="text-brand font-medium">Semua pertanyaan telah dijawab. Anda siap mengirimkan evaluasi.</span>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('learning.show', $material) }}"
                   class="px-3.5 py-1.5 text-xs font-medium border border-neutral-200 rounded-lg text-neutral-700 hover:bg-neutral-50 transition">
                    Batal
                </a>

                <button type="button"
                        wire:click="submitPostTest"
                        {{ $answeredCount === 0 ? 'disabled' : '' }}
                        class="px-4 py-1.5 text-xs font-medium bg-brand text-white rounded-lg hover:bg-brand-dark transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-1 focus:ring-brand">
                    Kirim Jawaban Evaluasi
                </button>
            </div>
        </div>
    @endif
</div>
