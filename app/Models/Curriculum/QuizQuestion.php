<?php

declare(strict_types=1);

namespace App\Models\Curriculum;

use App\Models\Tenancy\Tenant;
use Database\Factories\Curriculum\QuizQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * @property int $id
 * @property int $tenant_id
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
        'tenant_id',
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

    protected static function booted(): void
    {
        static::creating(function (QuizQuestion $question): void {
            if ($question->tenant_id !== null || $question->curriculum_item_id === null) {
                return;
            }

            $tenantId = CurriculumItem::query()
                ->whereKey($question->curriculum_item_id)
                ->value('tenant_id');
            if (!is_numeric($tenantId)) {
                throw new RuntimeException('Quiz question tenant_id could not be derived from curriculum item.');
            }

            $question->tenant_id = (int) $tenantId;
        });
    }

    /**
     * The curriculum item this question belongs to.
     *
     * @return BelongsTo<CurriculumItem, $this>
     */
    public function curriculumItem(): BelongsTo
    {
        return $this->belongsTo(CurriculumItem::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function newFactory(): QuizQuestionFactory
    {
        return QuizQuestionFactory::new();
    }
}
