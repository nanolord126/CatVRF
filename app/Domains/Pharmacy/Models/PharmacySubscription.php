<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class PharmacySubscription extends Model
{
    protected $table = 'pharmacy_subscriptions';
    protected $fillable = ['uuid', 'tenant_id', 'user_id', 'medicine_id', 'frequency', 'status', 'correlation_id', 'tags'];
    protected $casts = ['tags' => 'json'];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
        static::addGlobalScope('tenant', fn (Builder $b) => $b->where('tenant_id', tenant()->id));
    }
}
