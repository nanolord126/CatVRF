<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Earning extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'earnings';
        protected $fillable = [
            'tenant_id',
            'studio_id',
            'period_month',
            'period_year',
            'total_revenue',
            'total_commission',
            'studio_earnings',
            'total_bookings',
            'total_memberships_sold',
            'payout_initiated_at',
            'payout_completed_at',
            'payout_method',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'tags' => AsCollection::class,
            'total_revenue' => 'float',
            'total_commission' => 'float',
            'studio_earnings' => 'float',
            'payout_initiated_at' => 'datetime',
            'payout_completed_at' => 'datetime',
        ];

        public $timestamps = true;

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                $query->where('tenant_id', tenant('id'));
            });
        }

        public function studio(): BelongsTo
        {
            return $this->belongsTo(Studio::class);
        }
}
