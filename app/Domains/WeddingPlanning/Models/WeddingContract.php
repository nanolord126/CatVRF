<?php declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WeddingContract extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'wedding_contracts';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'event_id',
            'contract_number',
            'terms',
            'status',
            'signed_at',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'terms' => 'json',
            'tags' => 'json',
            'signed_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $builder->where('wedding_contracts.tenant_id', tenant()->id);
                }
            });

            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $model->tenant_id = $model->tenant_id ?? tenant()->id;
                }
            });
        }

        /**
         * Relation: Event
         */
        public function event(): BelongsTo
        {
            return $this->belongsTo(WeddingEvent::class, 'event_id');
        }
}
