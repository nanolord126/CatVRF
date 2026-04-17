<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\TenantScoped;

final class BeautyService extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'beauty_services';

    protected $fillable = [
        'salon_id',
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'description',
        'duration_minutes',
        'price_b2c',
        'price_b2b',
        'tags',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'json',
        'price_b2c' => 'decimal:2',
        'price_b2b' => 'decimal:2',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id');
    }
}
