<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Agro\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AgroCrop extends Model
{
    use HasFactory;

    use HasFactory, BelongsToTenant;

        protected $table = 'agro_crops';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'farm_id',
            'name',
            'variety',
            'planted_at',
            'harvest_expected_at',
            'status',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'tags' => 'json',
            'planted_at' => 'date',
            'harvest_expected_at' => 'date',
        ];

    

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function farm(): BelongsTo
        {
            return $this->belongsTo(AgroFarm::class, 'farm_id');
        }
}
