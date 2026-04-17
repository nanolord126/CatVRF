<?php declare(strict_types=1);

namespace App\Domains\ML\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use App\Models\User;

final class UserTasteProfile extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'category_preferences',
        'price_range',
        'brand_affinities',
        'behavioral_score',
        'metadata',
    ];

    protected $casts = [
        'category_preferences' => 'array',
        'price_range' => 'array',
        'brand_affinities' => 'array',
        'behavioral_score' => 'float',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPreferredCategories(): array
    {
        return $this->category_preferences ?? [];
    }
}
