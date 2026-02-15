<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QuizQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $curriculum_item_id
 * @property string $question
 * @property array<int, string> $options
 * @property array<int, int> $correct_answers
 * @property int $order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class QuizQuestion extends Model
{
    /** @use HasFactory<QuizQuestionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'curriculum_item_id',
        'question',
        'options',
        'correct_answers',
        'order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'options' => 'array',
        'correct_answers' => 'array',
    ];

    /**
     * The curriculum item this question belongs to.
     *
     * @return BelongsTo<CurriculumItem, $this>
     */
    public function curriculumItem(): BelongsTo
    {
        return $this->belongsTo(CurriculumItem::class);
    }
}
