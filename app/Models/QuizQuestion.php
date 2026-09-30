<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

#[Fillable([
    'quiz_id',
    'question_text',
    'order_index',
    'allow_multiple_answers',
])]
class QuizQuestion extends Model
{
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allow_multiple_answers' => 'boolean',
            'order_index' => 'integer',
        ];
    }

    /**
     * Get the quiz that owns this question.
     *
     * @return BelongsTo<Quiz, $this>
     */
    /**
     * Sisakan hanya format dari editor soal: tebal, miring, garis bawah, baris baru, dan tautan
     * http(s)/mailto. Dipakai saat menyimpan DAN saat menampilkan (soal lama/dari Filament).
     */
    public static function sanitizeText(string $html): string
    {
        static $sanitizer;
        $sanitizer ??= new HtmlSanitizer((new HtmlSanitizerConfig)
            ->allowElement('b')->allowElement('strong')
            ->allowElement('i')->allowElement('em')
            ->allowElement('u')->allowElement('br')
            ->allowElement('div')->allowElement('p')
            ->allowElement('a', ['href'])
            // Hasil tempel dari Word/web: buang tag-nya, pertahankan teksnya.
            ->blockElement('span')->blockElement('font')
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->forceAttribute('a', 'target', '_blank')
            ->forceAttribute('a', 'rel', 'noopener noreferrer'));

        return trim($sanitizer->sanitize($html));
    }

    /**
     * Teks soal siap tampil sebagai HTML (sudah disaring).
     */
    public function getQuestionHtmlAttribute(): string
    {
        return nl2br(self::sanitizeText((string) $this->question_text), false);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the options for this question.
     *
     * @return HasMany<QuizOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuizOption::class);
    }
}
