<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MeatConsumable extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'meat_consumables';
        protected $fillable = ['uuid', 'tenant_id', 'meat_shop_id', 'name', 'stock', 'min_threshold', 'correlation_id', 'tags'];
        protected $casts = ['tags' => 'json'];

        protected static function booted(): void
        {
            static::creating(fn ($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
            static::addGlobalScope('tenant', fn (Builder $b) => $b->where('tenant_id', tenant()->id));
        }
}
