<?php

declare(strict_types=1);

namespace App\Models\CourseCatalog;

use App\Models\Curriculum\Section;
use App\Models\Tenancy\Tenant;
use App\Models\IdentityAccess\User;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['tenant_id', 'name', 'description'];

    /**
     * The users enrolled in this course.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivot(['enrolled_at', 'tenant_id']);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the count of enrolled students.
     */
    public function enrollmentCount(): int
    {
        return $this->users()->count();
    }

    /**
     * The sections in this course.
     *
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)
            ->where('tenant_id', $this->tenant_id)
            ->orderBy('order');
    }

    protected static function newFactory(): CourseFactory
    {
        return CourseFactory::new();
    }
}
