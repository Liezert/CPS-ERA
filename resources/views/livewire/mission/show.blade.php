<div class="max-w-4xl mx-auto space-y-6">
    {{-- Breadcrumb Navigasi --}}
    <div class="flex items-center gap-2 text-xs text-neutral-600 font-medium">
        <a href="{{ route('missions.index') }}" class="hover:text-brand transition">Mission & Game</a>
        <span>/</span>
        <span class="text-neutral-900 font-semibold truncate">{{ $quiz->title }}</span>
    </div>

    {{-- Header Kartu Misi --}}
    <div class="bg-neutral-50/70 border border-neutral-200 rounded-lg p-5 sm:p-6 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                {{-- Badge Tipe (Stempel 2px) --}}
                <span class="inline-flex items-center gap-1.5 text-xs uppercase tracking-wider text-neutral-700 bg-white border border-neutral-200 px-2 py-0.5 rounded-[2px] font-sans">
                    @if ($quiz->isCaseStudy())
                        @include('components.layout.nav-icon', ['name' => 'book', 'class' => 'w-3.5 h-3.5 text-neutral-600'])
                        Studi Kasus
                    @else
                        @include('components.layout.nav-icon', ['name' => 'puzzle', 'class' => 'w-3.5 h-3.5 text-neutral-600'])
                        Quiz Cepat
                    @endif
                </span>

                {{-- Status Hasil Percobaan Sebelumnya --}}
                @if ($latestAttempt)
                    <span class="inline-flex items-center gap-1 text-xs border {{ $latestAttempt->passed ? 'border-brand text-brand-dark bg-brand-tint' : 'border-neutral-200 text-neutral-700 bg-white' }} px-2 py-0.5 rounded-[2px]">
                        @if ($latestAttempt->passed)
                            <svg class="w-3 h-3 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Pernah Lulus (Skor {{ $latestAttempt->score }})
                        @else
                            Percobaan Terakhir (Skor {{ $latestAttempt->score }})
                        @endif
                    </span>
                @endif
            </div>

            {{-- Reward Poin (Stempel 2px) --}}
            <span class="font-mono text-xs font-semibold text-brand-dark bg-brand-tint border border-brand/30 px-2.5 py-1 rounded-[2px]">
                Reward: +{{ $quiz->points_reward }} Poin
            </span>
        </div>

        <h1 class="text-xl sm:text-2xl font-semibold text-neutral-900 tracking-tight">
            {{ $quiz->title }}
        </h1>

        <div class="flex flex-wrap items-center gap-3 text-xs text-neutral-600 font-medium pt-1">
            <span>{{ $totalQuestions }} Pertanyaan Evaluasi</span>
            <span>•</span>
            <span>Standar Kelulusan: <strong class="text-neutral-800 font-semibold">70%</strong></span>
            <span>•</span>
            <span class="text-brand font-semibold">{{ $answeredCount }}/{{ $totalQuestions }} Terjawab</span>
        </div>

        {{-- Kebijakan Retry PRD §5.3 --}}
        <!-- TODO: Menunggu keputusan PRD §5.3 (Poin 4: Kebijakan retry kuis) -->
        <div class="pt-2 border-t border-neutral-200/80 flex flex-wrap items-center justify-between text-[11px] text-neutral-600">
            <span>Aturan Pengerjaan: Pilihan ganda dengan feedback evaluasi setelah selesai.</span>
            <span class="font-mono text-[10px] text-neutral-600 bg-white px-2 py-0.5 border border-neutral-200 rounded-badge">[Menunggu Keputusan PRD §5.3: Kebijakan Retry Kuis]</span>
        </div>
    </div>

    {{-- Skenario Kasus Manufaktur (Khusus Tipe Case Study) --}}
    @if ($quiz->isCaseStudy() && $quiz->description)
        <div class="bg-white border border-neutral-200 rounded-md p-5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-neutral-100 flex-wrap gap-2">
                <div class="flex items-center gap-2 text-xs font-semibold text-neutral-900 font-sans">
                    @include('components.layout.nav-icon', ['name' => 'document-text', 'class' => 'w-4 h-4 text-brand'])
                    <span>Skenario Studi Kasus Lapangan</span>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 border border-neutral-200 rounded-badge text-[10px] font-mono text-neutral-600 bg-neutral-50">
                    Analisis Pemecahan Masalah
                </span>
            </div>
            <div class="bg-neutral-50/70 border border-neutral-200 rounded-md p-4 text-xs sm:text-sm text-neutral-800 leading-relaxed whitespace-pre-line font-sans">
                {{ $quiz->description }}
            </div>
        </div>
    @endif

    {{-- Daftar Pertanyaan dan Opsi Jawaban Pilihan Ganda --}}
    <div class="space-y-4">
        @foreach ($quiz->questions as $index => $question)
            @php
                $evaluated = $evaluatedAnswers[$question->id] ?? null;
                $hasAnswered = isset($userAnswers[$question->id]);
            @endphp
            <div wire:key="question-box-{{ $question->id }}"
                 class="bg-white border border-neutral-200 rounded-lg p-5 space-y-4 transition">
                
                {{-- Header Soal --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <span class="text-[11px] font-mono uppercase tracking-wider text-neutral-400 block">
                            Pertanyaan {{ $index + 1 }} dari {{ $totalQuestions }}
                        </span>
                        <h3 class="text-sm font-medium text-neutral-900 leading-snug">
                            {{ $question->question_text }}
                        </h3>
                    </div>

                    {{-- Feedback Indikator Pojok Kanan Atas (DoD #1: Feedback Instan Tanpa Reload) --}}
                    @if ($evaluated)
                        @if ($evaluated['is_correct'])
                            <span class="shrink-0 inline-flex items-center gap-1 text-[11px] font-medium text-brand-dark bg-brand-tint border border-brand/40 px-2 py-0.5 rounded-[2px]">
                                <svg class="w-3.5 h-3.5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Benar
                            </span>
                        @else
                            <span class="shrink-0 inline-flex items-center gap-1 text-[11px] font-medium text-red-700 bg-red-50 border border-red-200 px-2 py-0.5 rounded-[2px]">
                                <svg class="w-3.5 h-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Kurang Tepat
                            </span>
                        @endif
                    @endif
                </div>

                {{-- Daftar Pilihan Ganda (Interactive Options) --}}
                <div class="space-y-2 pt-1">
                    @foreach ($question->options as $option)
                        @php
                            $isSelected = ($userAnswers[$question->id] ?? null) === $option->id;
                            $isThisCorrect = $evaluated && ($evaluated['correct_option_id'] === $option->id);
                            
                            // Logika Styling Feedback Instan (Design System §6: Tenang, Bebas Confetti)
                            $optionClasses = 'border-neutral-200 bg-white text-neutral-800 hover:border-neutral-300 hover:bg-neutral-50/50';
                            
                            if ($evaluated) {
                                if ($isSelected && $evaluated['is_correct']) {
                                    // Pilihan User & Benar (Hijau Tenang Brand)
                                    $optionClasses = 'border-brand bg-brand-tint/30 text-brand-dark font-medium';
                                } elseif ($isSelected && ! $evaluated['is_correct']) {
                                    // Pilihan User & Salah (Merah Tenang)
                                    $optionClasses = 'border-red-400 bg-red-50/50 text-red-700 font-medium';
                                } elseif ($isThisCorrect) {
                                    // Kunci Jawaban yang Benar (Border Hijau Tipis)
                                    $optionClasses = 'border-brand/40 bg-brand-tint/20 text-brand-dark';
                                } else {
                                    $optionClasses = 'border-neutral-200 bg-neutral-50/30 text-neutral-400 opacity-80';
                                }
                            }
                        @endphp

                        <button type="button"
                                wire:key="option-btn-{{ $option->id }}"
                                wire:click="selectOption('{{ $question->id }}', '{{ $option->id }}')"
                                class="w-full text-left p-3.5 border rounded-lg text-xs leading-relaxed transition flex items-center justify-between gap-3 {{ $optionClasses }} focus:outline-none focus:ring-1 focus:ring-brand">
                            
                            <div class="flex items-center gap-3">
                                {{-- Kotak Indikator Huruf --}}
                                <div class="w-5 h-5 rounded-full border flex items-center justify-center text-[10px] font-mono shrink-0
                                    {{ $isSelected ? ($evaluated && $evaluated['is_correct'] ? 'border-brand bg-brand text-white' : ($evaluated ? 'border-red-500 bg-red-600 text-white' : 'border-neutral-400 bg-neutral-200 text-neutral-700')) : 'border-neutral-300 text-neutral-500' }}">
                                    @if ($evaluated && $isSelected)
                                        @if ($evaluated['is_correct'])
                                            ✓
                                        @else
                                            ✕
                                        @endif
                                    @else
                                        •
                                    @endif
                                </div>
                                <span>{{ $option->option_text }}</span>
                            </div>

                            {{-- Penanda Solusi / Jawaban Benar Setelah Evaluasi --}}
                            @if ($evaluated)
                                <div class="shrink-0 text-[10px]">
                                    @if ($isSelected && $evaluated['is_correct'])
                                        <span class="text-brand-dark font-medium">Pilihan Tepat</span>
                                    @elseif ($isSelected && ! $evaluated['is_correct'])
                                        <span class="text-red-700 font-medium">Jawaban Anda</span>
                                    @elseif ($isThisCorrect)
                                        <span class="text-brand-dark font-medium">Kunci Jawaban</span>
                                    @endif
                                </div>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Panel Hasil Evaluasi Akhir & Poin Reward --}}
    @if ($isSubmitted)
        <div class="bg-white border border-neutral-200 rounded-lg p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-neutral-200">
                <div>
                    <span class="text-xs uppercase tracking-wider text-neutral-400 font-mono">Hasil Evaluasi Misi</span>
                    <h2 class="text-xl font-semibold text-neutral-900 mt-0.5">
                        @if ($passed)
                            Misi Berhasil Diselesaikan
                        @else
                            Misi Belum Memenuhi Passing Grade (Minimal 70%)
                        @endif
                    </h2>
                </div>

                {{-- Nilai Akhir --}}
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-semibold font-mono {{ $passed ? 'text-brand' : 'text-neutral-900' }}">
                        {{ $score }}
                    </span>
                    <span class="text-xs text-neutral-500">/ 100</span>
                </div>
            </div>

            {{-- Ringkasan Poin --}}
            <div class="text-xs text-neutral-600 space-y-1.5">
                @if ($passed)
                    @if ($pointsEarned > 0)
                        <div class="p-3 bg-brand-tint border border-brand/30 rounded-md text-brand-dark flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Selamat! <strong>+{{ $pointsEarned }} Poin</strong> telah berhasil tercatat ke ledger point_transactions akun Anda.</span>
                        </div>
                    @else
                        <div class="p-3 bg-neutral-50 border border-neutral-200 rounded-md text-neutral-700 flex items-center gap-2">
                            <svg class="w-4 h-4 text-neutral-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                            <span>Poin misi ini (+{{ $quiz->points_reward }} Poin) sudah pernah Anda klaim pada penyelesaian sebelumnya.</span>
                        </div>
                    @endif
                @else
                    <div class="p-3 bg-neutral-50 border border-neutral-200 rounded-md text-neutral-700">
                        Skor Anda belum mencapai ambang batas 70%. Anda dapat meninjau kembali kunci jawaban di atas atau mengulang pengerjaan.
                    </div>
                @endif
            </div>

            {{-- DoD #3: Penandaan Kebijakan Retry PRD §5.3 --}}
            <div class="p-3 bg-neutral-50 border border-neutral-200 rounded-md text-xs text-neutral-600">
                <div class="font-semibold text-neutral-800 mb-1">
                    [Menunggu Keputusan PRD §5.3: Kebijakan Retry Kuis]
                </div>
                <p class="leading-relaxed">
                    Kebijakan retry misi saat ini belum disahkan secara final oleh manajemen. Sesuai aturan interim MVP, Anda diperbolehkan mengulang misi untuk sarana belajar, namun pencatatan poin ke buku besar <code>point_transactions</code> hanya diberikan 1x seumur hidup per misi.
                </p>
            </div>

            {{-- Tombol Aksi Bawah --}}
            <div class="pt-2 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('missions.index') }}"
                   class="px-4 py-2 text-xs font-medium border border-neutral-200 rounded-lg text-neutral-700 hover:bg-neutral-50 transition">
                    Kembali ke Daftar Misi
                </a>

                <button type="button"
                        wire:click="resetQuiz"
                        class="px-4 py-2 text-xs font-medium border border-neutral-200 rounded-lg text-neutral-900 hover:bg-neutral-50 transition">
                    Coba Ulang Misi (Retry)
                </button>
            </div>
        </div>
    @else
        {{-- Kontrol Aksi Submit Pengerjaan --}}
        <div class="bg-white border border-neutral-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="text-xs text-neutral-500">
                @if ($answeredCount < $totalQuestions)
                    <span>Masih ada {{ $totalQuestions - $answeredCount }} pertanyaan yang belum dijawab.</span>
                @else
                    <span class="text-brand font-medium">Semua pertanyaan telah dijawab. Anda siap menyelesaikan misi.</span>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('missions.index') }}"
                   class="px-3.5 py-1.5 text-xs font-medium border border-neutral-200 rounded-lg text-neutral-700 hover:bg-neutral-50 transition">
                    Batal
                </a>

                <button type="button"
                        wire:click="submitQuiz"
                        wire:loading.attr="disabled"
                        wire:target="submitQuiz"
                        {{ $answeredCount === 0 ? 'disabled' : '' }}
                        class="inline-flex items-center gap-2 px-4 py-1.5 text-xs font-medium bg-brand text-white rounded-lg hover:bg-brand-dark transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-1">
                    <span wire:loading.remove wire:target="submitQuiz">Selesaikan & Hitung Nilai</span>
                    <span wire:loading wire:target="submitQuiz" class="flex items-center gap-2">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Menghitung Nilai...</span>
                    </span>
                </button>
            </div>
        </div>
    @endif
</div>
