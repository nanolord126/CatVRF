<?php declare(strict_types=1);

namespace App\Domains\Archived\Pharmacy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PharmacySubscription extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'pharmacy_subscriptions';


        protected $fillable = ['uuid', 'tenant_id', 'user_id', 'medicine_id', 'frequency', 'status', 'correlation_id', 'tags'];


        protected $casts = ['tags' => 'json'];


        protected static function booted(): void


        {


            static::creating(fn ($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());


            static::addGlobalScope('tenant', fn (Builder $b) => $b->where('tenant_id', tenant()->id));


        }
}
