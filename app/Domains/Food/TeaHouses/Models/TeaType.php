<?php declare(strict_types=1);

namespace App\Domains\Food\TeaHouses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TeaType extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'tea_types';
        protected $fillable = ['uuid', 'tenant_id', 'house_id', 'correlation_id', 'name', 'price_kopecks', 'origin', 'brewing_temp', 'tags'];
        protected $casts = ['price_kopecks' => 'integer', 'brewing_temp' => 'integer', 'tags' => 'json'];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function house() { return $this->belongsTo(TeaHouse::class, 'house_id'); }

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('tea_types.tenant_id', tenant()->id));
        }
}
