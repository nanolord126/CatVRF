<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Models;

use HasFactory, SoftDeletes;
use HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
     * VeganStore Model - Physical and Virtual Points of Presence.
     */
final class VeganStore extends Model
{
        use HasFactory, SoftDeletes;
        protected $table = 'vegan_stores';
        protected $fillable = ['uuid', 'tenant_id', 'name', 'address', 'schedule', 'certification_id', 'is_active', 'rating', 'correlation_id', 'tags'];
        protected $casts = ['schedule' => 'json', 'tags' => 'json', 'is_active' => 'boolean'];

        protected static function booted(): void
        {
            static::creating(fn ($m) => $m->uuid = $m->uuid ?: (string) Str::uuid());
            static::addGlobalScope('tenant', fn ($b) => tenant() ? $b->where('tenant_id', tenant()->id) : null);
        }

        public function products(): HasMany { return $this->hasMany(VeganProduct::class, 'vegan_store_id'); }
    }
