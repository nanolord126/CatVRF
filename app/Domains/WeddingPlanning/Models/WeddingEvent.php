<?php declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class WeddingEvent extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'wedding_events';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'owner_id',
            'title',
            'event_date',
            'location',
            'guest_count',
            'total_budget',
            'status',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'event_date' => 'datetime',
            'guest_count' => 'integer',
            'total_budget' => 'integer',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $builder->where('wedding_events.tenant_id', tenant()->id);
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
         * Relation: Bookings
         */
        public function bookings(): HasMany
        {
            return $this->hasMany(WeddingBooking::class, 'event_id');
        }

        /**
         * Relation: Contracts
         */
        public function contracts(): HasMany
        {
            return $this->hasMany(WeddingContract::class, 'event_id');
        }

        /**
         * Relation: Reviews
         */
        public function reviews(): HasMany
        {
            return $this->hasMany(WeddingReview::class, 'event_id');
        }
}
