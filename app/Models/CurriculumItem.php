<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Tenancy\Tenant;
use Database\Factories\CurriculumItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

/**
 * @property int $id
 * @property int $tenant_id
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
        'tenant_id',
        'section_id',
        'type',
        'title',
        'duration_minutes',
        'order',
        'video_path',
        'assignment_content',
    ];

    protected static function booted(): void
    {
        static::creating(function (CurriculumItem $item): void {
            if ($item->tenant_id !== null || $item->section_id === null) {
                return;
            }

            $tenantId = Section::query()
                ->whereKey($item->section_id)
                ->value('tenant_id');
            if (!is_numeric($tenantId)) {
                throw new RuntimeException('Curriculum item tenant_id could not be derived from section.');
            }

            $item->tenant_id = (int) $tenantId;
        });
    }

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
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The quiz questions for this curriculum item.
     *
     * @return HasMany<QuizQuestion, $this>
     */
    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)
            ->where('tenant_id', $this->tenant_id)
            ->orderBy('order');
    }
}
