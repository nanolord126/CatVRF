<?php declare(strict_types=1);

namespace App\Domains\Archived\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ApartmentBooking extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;


        protected $fillable = [


            'tenant_id', 'apartment_id', 'user_id', 'inn',


            'business_card_id', 'check_in', 'check_out',


            'guests_count', 'total_price', 'deposit_held',


            'status', 'payment_status', 'uuid',


            'correlation_id', 'tags',


        ];


        protected $casts = [


            'check_in' => 'date', 'check_out' => 'date',


            'tags' => 'json', 'total_price' => 'decimal:2',


            'deposit_held' => 'decimal:2', 'guests_count' => 'integer',


        ];


        protected static function booted(): void


        {


            static::addGlobalScope('tenant', fn($q) =>


                $q->where('tenant_id', tenant()->id ?? 0)


            );


        }


        public function apartment(): BelongsTo


        {


            return $this->belongsTo(Apartment::class);


        }
}
