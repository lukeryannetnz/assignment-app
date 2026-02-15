<?php

declare(strict_types=1);

namespace App\Models\Curriculum;

use App\Models\CourseCatalog\Course;
use App\Models\Tenancy\Tenant;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $course_id
 * @property string $title
 * @property int $order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['tenant_id', 'course_id', 'title', 'order'];

    protected static function booted(): void
    {
        static::creating(function (Section $section): void {
            if ($section->tenant_id !== null || $section->course_id === null) {
                return;
            }

            $tenantId = Course::query()
                ->whereKey($section->course_id)
                ->value('tenant_id');
            if (!is_numeric($tenantId)) {
                throw new RuntimeException('Section tenant_id could not be derived from course.');
            }

            $section->tenant_id = (int) $tenantId;
        });
    }

    /**
     * The course this section belongs to.
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The curriculum items in this section.
     *
     * @return HasMany<CurriculumItem, $this>
     */
    public function curriculumItems(): HasMany
    {
        return $this->hasMany(CurriculumItem::class)
            ->where('tenant_id', $this->tenant_id)
            ->orderBy('order');
    }

    protected static function newFactory(): SectionFactory
    {
        return SectionFactory::new();
    }
}
