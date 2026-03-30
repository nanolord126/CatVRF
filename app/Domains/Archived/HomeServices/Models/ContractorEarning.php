<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ContractorEarning extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'contractor_earnings';


        protected $fillable = ['tenant_id', 'contractor_id', 'period_month', 'period_year', 'total_revenue', 'total_commission', 'contractor_earnings', 'total_jobs', 'completed_jobs', 'average_rating', 'payout_initiated_at', 'payout_completed_at', 'payout_method', 'correlation_id'];


        protected $hidden = [];


        protected $casts = ['total_revenue' => 'float', 'total_commission' => 'float', 'contractor_earnings' => 'float', 'average_rating' => 'float', 'payout_initiated_at' => 'datetime', 'payout_completed_at' => 'datetime'];


        protected static function booted(): void


        {


            static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant('id')));


        }


        public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }


        public function contractor(): BelongsTo { return $this->belongsTo(Contractor::class); }
}
