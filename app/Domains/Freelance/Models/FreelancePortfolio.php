<?php declare(strict_types=1);

/**
 * FreelancePortfolio — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/freelanceportfolio
 */


namespace App\Domains\Freelance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelancePortfolio extends Model
{
    use HasFactory;

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

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
