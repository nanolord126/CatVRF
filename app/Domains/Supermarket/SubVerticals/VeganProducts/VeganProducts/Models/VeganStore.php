<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\VeganProducts\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * VeganStore Model - Physical and Virtual Points of Presence.
 */
final class VeganStore extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'vegan_stores';

    protected $fillable = ['uuid', 'tenant_id', 'name', 'address', 'schedule', 'certification_id', 'is_active', 'rating', 'correlation_id', 'tags'];

    protected $casts = ['schedule' => 'json', 'tags' => 'json', 'is_active' => 'boolean'];

    public function products(): HasMany
    {
        return $this->hasMany(VeganProduct::class, 'vegan_store_id');
    }

    protected static function booted(): void
    {
        self::creating(fn ($m) => $m->uuid = $m->uuid ?: (string) Str::uuid());
        self::addGlobalScope('tenant', fn ($b) => tenant() ? $b->where('tenant_id', tenant()->id) : null);
    }
}
