<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CurriculumItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $section_id
 * @property string $type
 * @property string $title
 * @property int $duration_minutes
 * @property int $order
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
    protected $fillable = ['section_id', 'type', 'title', 'duration_minutes', 'order'];

    /**
     * The section this curriculum item belongs to.
     *
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
