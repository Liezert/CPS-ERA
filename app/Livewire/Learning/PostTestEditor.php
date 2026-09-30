<?php

namespace App\Livewire\Learning;

use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Editor post-test ala Google Form: satu kartu per soal, jawaban benar ditandai dengan mengklik
 * lingkaran/kotak opsinya. Semua perubahan tersimpan sekaligus lewat tombol Simpan.
 * Format data sama dengan editor Filament (Quiz type=post_test, related_type=learning_material).
 */
#[Layout('layouts.app')]
#[Title('Kelola Post-Test - CPS ERA')]
class PostTestEditor extends Component
{
    public LearningMaterial $material;

    public string $title = '';

    public string $description = '';

    public int $pointsReward = 20;

    /**
     * id = kunci tetap per kartu (bukan disimpan): editor teks soal ikut pindah saat kartu digeser.
     *
     * @var list<array{id: string, text: string, multiple: bool, options: list<array{text: string, correct: bool}>}>
     */
    public array $questions = [];

    /** Soal terakhir yang dihapus, untuk tombol "Urungkan". */
    public ?array $lastRemoved = null;

    /** Bertambah tiap penghapusan agar notifikasi "Soal dihapus" muncul ulang. */
    public int $removedCount = 0;

    public function mount(LearningMaterial $material): void
    {
        $this->material = $material;
        $postTest = $material->postTest()->with('questions.options')->first();

        Gate::authorize($postTest ? 'update' : 'create', $postTest ?? Quiz::class);

        if (! $postTest) {
            $this->title = 'Post-Test: '.$material->title;
            $this->questions = [$this->blankQuestion()];

            return;
        }

        $this->title = $postTest->title;
        $this->description = (string) $postTest->description;
        $this->pointsReward = (int) $postTest->points_reward;
        $this->questions = $postTest->questions->map(fn ($question) => [
            'id' => Str::random(8),
            // Soal lama berupa teks biasa: jadikan HTML aman (baris baru -> <br>) untuk editor.
            'text' => $question->question_html,
            'multiple' => (bool) $question->allow_multiple_answers,
            'options' => $question->options->map(fn ($option) => [
                'text' => $option->option_text,
                'correct' => (bool) $option->is_correct,
            ])->values()->all(),
        ])->values()->all();
    }

    /**
     * @return array{text: string, multiple: bool, options: list<array{text: string, correct: bool}>}
     */
    private function blankQuestion(): array
    {
        return ['id' => Str::random(8), 'text' => '', 'multiple' => false, 'options' => [['text' => '', 'correct' => false], ['text' => '', 'correct' => false]]];
    }

    public function addQuestion(): void
    {
        $this->questions[] = $this->blankQuestion();
    }

    public function duplicateQuestion(int $q): void
    {
        array_splice($this->questions, $q + 1, 0, [['id' => Str::random(8)] + $this->questions[$q]]);
    }

    /**
     * Hapus langsung tanpa dialog konfirmasi (dialog browser bisa diblokir Chrome), seperti Google Form:
     * notifikasi "Soal dihapus" dengan tombol Urungkan.
     */
    public function removeQuestion(int $q): void
    {
        if (! isset($this->questions[$q])) {
            return;
        }

        $this->lastRemoved = ['index' => $q, 'question' => $this->questions[$q]];
        $this->removedCount++;
        array_splice($this->questions, $q, 1);
        $this->resetValidation();
    }

    public function undoRemove(): void
    {
        if ($this->lastRemoved === null) {
            return;
        }

        $index = min($this->lastRemoved['index'], count($this->questions));
        array_splice($this->questions, $index, 0, [$this->lastRemoved['question']]);
        $this->lastRemoved = null;
    }

    public function moveQuestion(int $q, int $direction): void
    {
        $target = $q + $direction;
        if (! isset($this->questions[$q], $this->questions[$target])) {
            return;
        }

        [$this->questions[$q], $this->questions[$target]] = [$this->questions[$target], $this->questions[$q]];
    }

    public function addOption(int $q): void
    {
        $this->questions[$q]['options'][] = ['text' => '', 'correct' => false];
    }

    public function removeOption(int $q, int $o): void
    {
        array_splice($this->questions[$q]['options'], $o, 1);
    }

    /**
     * Klik penanda opsi = tandai jawaban benar. Pilihan ganda hanya satu jawaban benar,
     * kotak centang boleh lebih dari satu.
     */
    public function toggleCorrect(int $q, int $o): void
    {
        $question = &$this->questions[$q];
        $wasCorrect = $question['options'][$o]['correct'];

        if (! $question['multiple']) {
            foreach ($question['options'] as &$option) {
                $option['correct'] = false;
            }
            unset($option);
        }

        $question['options'][$o]['correct'] = ! $wasCorrect;
    }

    /**
     * Ganti jenis soal. Dari kotak centang ke pilihan ganda, hanya jawaban benar pertama yang dipertahankan.
     */
    public function setMultiple(int $q, bool $multiple): void
    {
        $this->questions[$q]['multiple'] = $multiple;

        if (! $multiple) {
            $kept = false;
            foreach ($this->questions[$q]['options'] as &$option) {
                $option['correct'] = $option['correct'] && ! $kept;
                $kept = $kept || $option['correct'];
            }
            unset($option);
        }
    }

    public function save()
    {
        // Teks soal dari editor berformat HTML: saring dulu, dan anggap kosong jika tanpa teks (mis. hanya <br>).
        foreach ($this->questions as $q => $question) {
            $clean = QuizQuestion::sanitizeText((string) $question['text']);
            $this->questions[$q]['text'] = trim(html_entity_decode(strip_tags($clean))) === '' ? '' : $clean;
        }

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'pointsReward' => ['required', 'integer', 'min:0', 'max:1000'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.text' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.options.*.text' => ['required', 'string', 'max:500'],
        ], [
            'title.required' => 'Judul post-test wajib diisi.',
            'questions.required' => 'Tambahkan minimal satu soal.',
            'questions.*.text.required' => 'Pertanyaan wajib diisi.',
            'questions.*.options.min' => 'Minimal dua opsi jawaban.',
            'questions.*.options.*.text.required' => 'Teks opsi wajib diisi.',
        ]);

        // Tiap soal harus punya kunci jawaban; tanpa itu karyawan mustahil lulus (lulus = semua benar).
        $missingKey = false;
        foreach ($this->questions as $q => $question) {
            if (! collect($question['options'])->contains('correct', true)) {
                $this->addError("questions.{$q}.correct", 'Tandai minimal satu jawaban benar (klik lingkaran di kiri opsi).');
                $missingKey = true;
            }
        }
        if ($missingKey) {
            return null;
        }

        DB::transaction(function (): void {
            $postTest = $this->material->postTest()->first() ?? new Quiz([
                'type' => 'post_test',
                'related_type' => 'learning_material',
                'related_id' => $this->material->id,
            ]);

            $postTest->fill([
                'title' => trim($this->title),
                'description' => trim($this->description) ?: null,
                'points_reward' => $this->pointsReward,
            ])->save();

            // Soal disinkron ulang seperti editor Filament; riwayat attempt hanya menyimpan skor, bukan ID soal.
            QuizQuestion::where('quiz_id', $postTest->id)->delete(); // opsi ikut terhapus (cascade)

            foreach ($this->questions as $index => $question) {
                $saved = $postTest->questions()->create([
                    'question_text' => trim($question['text']),
                    'order_index' => $index + 1,
                    'allow_multiple_answers' => $question['multiple'],
                ]);

                $saved->options()->createMany(array_map(fn (array $option): array => [
                    'option_text' => trim($option['text']),
                    'is_correct' => $option['correct'],
                ], $question['options']));
            }
        });

        session()->flash('status', 'Post-test tersimpan ('.count($this->questions).' soal).');

        return $this->redirect(route('learning.post-test.edit', $this->material), navigate: true);
    }

    public function render()
    {
        return view('livewire.learning.post-test-editor');
    }
}
