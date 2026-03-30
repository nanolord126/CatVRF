<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OfficeMenu extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'office_menus';
        protected $fillable = [
            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
            'client_id', 'name', 'items', 'items_count',
            'price_per_serving', 'min_portions', 'active', 'tags',
        ];
        protected $casts = [
            'items'              => 'json',
            'items_count'        => 'int',
            'price_per_serving'  => 'int',
            'min_portions'       => 'int',
            'active'             => 'boolean',
            'tags'               => 'json',
        ];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \Exception
         */
        public function client(): BelongsTo
        {
            return $this->belongsTo(CorporateClient::class, 'client_id');
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }
}
