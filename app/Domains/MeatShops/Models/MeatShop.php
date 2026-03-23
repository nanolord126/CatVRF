<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class MeatShop extends Model
{
    protected $table = 'meat_shops';
    protected $fillable = ['uuid', 'tenant_id', 'business_group_id', 'name', 'address', 'correlation_id', 'tags'];
    protected $casts = ['tags' => 'json'];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
        static::addGlobalScope('tenant', fn (Builder $b) => $b->where('tenant_id', tenant()->id));
    }

    public function products() { return $this->hasMany(MeatProduct::class); }
}
