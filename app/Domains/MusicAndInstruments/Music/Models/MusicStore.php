<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicStore extends Model
{

    use HasFactory, SoftDeletes;

        protected $table = 'music_stores';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'correlation_id',
            'name',
            'slug',
            'address',
            'geo_point',
            'schedule',
            'rating',
            'review_count',
            'is_verified',
            'type',
            'tags',
        ];

        protected $casts = [
            'geo_point' => 'json',
            'schedule' => 'json',
            'is_verified' => 'boolean',
            'rating' => 'float',
            'tags' => 'array',
        ];

        /**
         * The "booted" method of the model.
         */
        protected static function booted_disabled(): void
        {
            static::creating(function ($model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) Str::uuid());

                // Tenant scoping
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = tenant()->id ?? 'null';
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Get the instruments for the store.
         */
        public function instruments(): HasMany
        {
            return $this->hasMany(MusicInstrument::class);
        }

        /**
         * Get the accessories for the store.
         */
        public function accessories(): HasMany
        {
            return $this->hasMany(MusicAccessory::class);
        }

        /**
         * Get the studios for the store.
         */
        public function studios(): HasMany
        {
            return $this->hasMany(MusicStudio::class);
        }

        /**
         * Get the lessons for the store.
         */
        public function lessons(): HasMany
        {
            return $this->hasMany(MusicLesson::class);
        }
}
