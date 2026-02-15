<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CurriculumItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $section_id
 * @property string $type
 * @property string $title
 * @property int $duration_minutes
 * @property int $order
 * @property string|null $video_path
 * @property string|null $assignment_content
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class CurriculumItem extends Model
{
    /** @use HasFactory<CurriculumItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'section_id',
        'type',
        'title',
        'duration_minutes',
        'order',
        'video_path',
        'assignment_content',
    ];

    /**
     * The section this curriculum item belongs to.
     *
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * The quiz questions for this curriculum item.
     *
     * @return HasMany<QuizQuestion, $this>
     */
    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }
}
