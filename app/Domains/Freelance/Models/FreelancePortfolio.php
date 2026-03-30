<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelancePortfolio extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'freelance_portfolios';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'freelancer_id',
            'title',
            'description',
            'media_urls',
            'case_url',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'media_urls' => 'json',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function ($builder) {
                $builder->where('tenant_id', tenant()->id ?? 1);
            });
        }

        public function freelancer(): BelongsTo
        {
            return $this->belongsTo(Freelancer::class);
        }
}
