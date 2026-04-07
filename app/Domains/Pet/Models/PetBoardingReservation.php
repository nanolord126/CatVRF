<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetBoardingReservation extends Model
{

    use HasFactory;
        use SoftDeletes;

        protected $table = 'pet_boarding_reservations';

        protected $fillable = [
            'tenant_id',
            'clinic_id',
            'owner_id',
            'reservation_number',
            'pet_name',
            'pet_type',
            'check_in_at',
            'check_out_at',
            'actual_check_in',
            'actual_check_out',
            'status',
            'payment_status',
            'price_per_day',
            'total_amount',
            'commission_amount',
            'notes',
            'transaction_id',
            'correlation_id',
            'uuid',
        ];

        protected $casts = [
            'price_per_day' => 'float',
            'total_amount' => 'float',
            'commission_amount' => 'float',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'actual_check_in' => 'datetime',
            'actual_check_out' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        protected $hidden = ['correlation_id', 'transaction_id'];

        public function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function clinic(): BelongsTo
        {
            return $this->belongsTo(PetClinic::class);
        }

        public function owner(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }
}
