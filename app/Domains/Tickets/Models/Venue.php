<?php declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Venue extends Model
{


        protected $table = 'venues';

        protected $fillable = [
            'uuid', 'tenant_id', 'business_group_id',
            'name', 'address', 'capacity', 'contacts',
            'is_active', 'tags', 'correlation_id'
        ];

        protected $casts = [
            'capacity' => 'integer',
            'is_active' => 'boolean',
            'contacts' => 'json',
            'tags' => 'json'
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()?->id) {
                    $builder->where('tenant_id', tenant()?->id);
                }
            });

            static::creating(function ($model) {
                $model->uuid = (string) Str::uuid();
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = tenant()?->id;
                }
            });
        }

        

        /**
         * Все эвенты площадки.
         */
        public function events(): HasMany
        {
            return $this->hasMany(Event::class);
        }

        /**
         * Схемы залов площадки.
         */
        public function seatMaps(): HasMany
        {
            return $this->hasMany(SeatMap::class);
        }

        /**
         * Получить основные контактные данные.
         */
        public function getPhoneAttribute(): ?string
        {
            return $this->contacts['phone'] ?? null;
        }

        public function getEmailAttribute(): ?string
        {
            return $this->contacts['email'] ?? null;
        }
}
