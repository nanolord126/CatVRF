<?php declare(strict_types=1);

namespace App\Domains\Photography\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Portfolio extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'photography_portfolios';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'photographer_id',
            'title',
            'description',
            'media_urls',
            'style_tag',
            'correlation_id'
        ];

        protected $casts = [
            'uuid' => 'string',
            'media_urls' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid ??= (string) Str::uuid();
                $model->tenant_id ??= tenant()?->id;
            });

            static::addGlobalScope('tenant', function ($builder) {
                if (tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        public function photographer(): BelongsTo
        {
            return $this->belongsTo(Photographer::class, 'photographer_id');
        }
}
