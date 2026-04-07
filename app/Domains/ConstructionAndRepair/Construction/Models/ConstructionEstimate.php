<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\Construction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConstructionEstimate extends Model
{
    use HasFactory;

    use HasFactory, BelongsToTenant;

        protected $table = 'construction_estimates';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'project_id',
            'name',
            'total_cost_kopeks',
            'status',
            'items_json',
            'correlation_id',
        ];

        protected $casts = [
            'items_json' => 'json',
        ];

    

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function project(): BelongsTo
        {
            return $this->belongsTo(ConstructionProject::class, 'project_id');
        }
}
