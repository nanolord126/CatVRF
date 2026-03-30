<?php declare(strict_types=1);

namespace App\Domains\Archived\MusicAndInstruments\Music\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicInstrument extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;


        protected $table = 'music_instruments';


        protected $fillable = [


            'uuid',


            'music_store_id',


            'tenant_id',


            'correlation_id',


            'name',


            'brand',


            'model',


            'category',


            'condition',


            'price_cents',


            'rental_price_cents',


            'stock',


            'hold_stock',


            'specifications',


            'tags',


        ];


        protected $casts = [


            'specifications' => 'array',


            'tags' => 'array',


            'price_cents' => 'integer',


            'rental_price_cents' => 'integer',


            'stock' => 'integer',


            'hold_stock' => 'integer',


        ];


        /**


         * The "booted" method of the model.


         */


        protected static function booted(): void


        {


            static::creating(function ($model) {


                $model->uuid = $model->uuid ?? (string) Str::uuid();


                $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());


                // Tenant scoping


                if (empty($model->tenant_id) && function_exists('tenant')) {


                    $model->tenant_id = tenant()->id ?? 'null';


                }


            });


            static::addGlobalScope('tenant', function ($builder) {


                if (function_exists('tenant') && tenant()) {


                    $builder->where('music_instruments.tenant_id', tenant()->id);


                }


            });


        }


        /**


         * Get the store that owns the instrument.


         */


        public function store(): BelongsTo


        {


            return $this->belongsTo(MusicStore::class, 'music_store_id');


        }


        /**


         * Get the instrument's reviews.


         */


        public function reviews(): MorphMany


        {


            return $this->morphMany(MusicReview::class, 'reviewable');


        }


        /**


         * Get the instrument's bookings (rentals).


         */


        public function bookings(): MorphMany


        {


            return $this->morphMany(MusicBooking::class, 'bookable');


        }


        /**


         * Total stock available.


         */


        public function availableStock(): int


        {


            return $this->stock - $this->hold_stock;


        }
}
