<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\VeganProducts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * VeganCategory Model - Classification of Plant-Based Goods.
 */
final class VeganCategory extends Model
{
    protected $table = 'vegan_categories';

    protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'description', 'icon', 'correlation_id'];

    public function products(): HasMany
    {
        return $this->hasMany(VeganProduct::class, 'vegan_category_id');
    }
}
