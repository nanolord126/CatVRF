<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourierRating extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'courier_ratings';

        protected $fillable = [
            'tenant_id',
            'courier_service_id',
            'reviewer_id',
            'rating',
            'comment',
            'media',
            'verified_transaction',
            'correlation_id',
        ];

        protected $casts = [
            'media' => 'collection',
            'verified_transaction' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (auth()->check()) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }

        public function courierService(): BelongsTo
        {
            return $this->belongsTo(CourierService::class);
        }

        public function reviewer(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }
}
