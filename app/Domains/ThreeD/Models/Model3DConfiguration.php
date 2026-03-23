<?php

declare(strict_types=1);

namespace App\Domains\ThreeD\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsJson;

final class Model3DConfiguration extends Model
{
    protected $table = 'model_3d_configurations';
    public $timestamps = true;

    protected $fillable = [
        'tenant_id',
        'model_3d_id',
        'name',
        'config',
        'price_modifier',
        'status',
        'usage_count',
        'correlation_id',
    ];

    protected $casts = [
        'config' => AsJson::class,
        'price_modifier' => 'decimal:2',
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'correlation_id',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function model3D(): BelongsTo
    {
        return $this->belongsTo(Model3D::class, 'model_3d_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Инкремент счётчика использований
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
