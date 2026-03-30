<?php declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WeddingPackage extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'wedding_packages';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'planner_id',
            'title',
            'description',
            'price',
            'max_guests',
            'included_services',
            'is_active',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'included_services' => 'json',
            'tags' => 'json',
            'price' => 'integer',
            'max_guests' => 'integer',
            'is_active' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $builder->where('wedding_packages.tenant_id', tenant()->id);
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
         * Relation: Planner
         */
        public function planner(): BelongsTo
        {
            return $this->belongsTo(WeddingPlanner::class, 'planner_id');
        }

        /**
         * Relation: Bookings
         */
        public function bookings(): HasMany
        {
            return $this->morphMany(WeddingBooking::class, 'bookable');
        }
}
