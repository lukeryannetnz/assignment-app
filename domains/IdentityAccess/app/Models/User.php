<?php

declare(strict_types=1);

namespace App\Domains\IdentityAccess\Models;

use App\Domains\CourseCatalog\Models\Course;
use App\Domains\Tenancy\Models\Tenant;
use Database\Factories\IdentityAccess\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_student
 * @property bool $is_admin
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'is_student',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_student' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * The courses this user is enrolled in.
     *
     * @return BelongsToMany<Course, $this>
     */
    public function courses(): BelongsToMany
    {
        $relation = $this->belongsToMany(Course::class)
            ->withTimestamps()
            ->withPivot(['enrolled_at', 'tenant_id']);

        if ($this->tenant_id !== null) {
            $relation = $relation
                ->withPivotValue('tenant_id', $this->tenant_id)
                ->wherePivot('tenant_id', $this->tenant_id);
        }

        return $relation;
    }

    /**
     * The tenant this user belongs to.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Check if user is a student.
     */
    public function isStudent(): bool
    {
        return $this->is_student;
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
