<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicAccessory extends Model
{

        protected $table = 'music_accessories';

        protected $fillable = [
            'uuid',
            'music_store_id',
            'tenant_id',
            'correlation_id',
            'sku',
            'name',
            'type',
            'price_cents',
            'stock',
            'min_stock_threshold',
            'tags',
        ];

        protected $casts = [
            'tags' => 'array',
            'price_cents' => 'integer',
            'stock' => 'integer',
            'min_stock_threshold' => 'integer',
        ];

        protected static function booted_disabled(): void
        {
            static::creating(function ($model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) Str::uuid());

                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = tenant()->id ?? 'null';
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('music_accessories.tenant_id', tenant()->id);
                }
            });
        }

        public function store(): BelongsTo
        {
            return $this->belongsTo(MusicStore::class, 'music_store_id');
        }
}
