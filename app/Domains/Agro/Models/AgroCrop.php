<?php
declare(strict_types=1);

namespace App\Domains\Agro\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $farm_id
 * @property string $name
 * @property string|null $variety
 * @property string|null $status
 */
final class AgroCrop extends Model
{
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

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(AgroFarm::class, 'farm_id');
    }
}
