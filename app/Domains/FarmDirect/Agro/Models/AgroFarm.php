<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Agro\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AgroFarm extends Model
{
    use HasFactory;

    use HasFactory, BelongsToTenant, SoftDeletes;

        protected $table = 'agro_farms';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'address',
            'inn',
            'kpp',
            'description',
            'specialization', // jsonb: [meat, milk, grain]
            'geo_location',   // Point/jsonb
            'is_verified',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'specialization' => 'array',
            'is_verified' => 'boolean',
            'tags' => 'json',
        ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) Str::uuid());
            });
        }

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
        {
            return $this->hasMany(AgroProduct::class, 'farm_id');
        }
}
