<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Models;

use Database\Factories\Tenancy\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $status
 * @property string $plan_tier
 * @property int $hierarchy_depth_limit
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'status',
        'plan_tier',
        'hierarchy_depth_limit',
    ];

    /**
     * @return HasMany<OrgNode, $this>
     */
    public function orgNodes(): HasMany
    {
        return $this->hasMany(OrgNode::class);
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }
}
