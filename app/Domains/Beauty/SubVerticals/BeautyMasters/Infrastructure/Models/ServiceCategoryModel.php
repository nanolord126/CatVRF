<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ServiceCategoryModel extends Model
{
    use HasFactory;

    protected $table = 'beauty_service_categories';

    protected $fillable = [
        'venue_id',
        'name',
        'slug',
        'icon',
        'color',
        'description',
        'is_active',
        'sort_order',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ServiceCategoryModel::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ServiceCategoryModel::class, 'parent_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(ServiceModel::class, 'category_id');
    }
}
