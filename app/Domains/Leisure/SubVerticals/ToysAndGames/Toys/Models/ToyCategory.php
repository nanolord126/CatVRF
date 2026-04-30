<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\ToysAndGames\Toys\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ToyCategory Model (L1)
 * Taxonomy: Puzzles, Lego, Sensory, Boards.
 */
final class ToyCategory extends Model
{
    use TenantScoped;
    use ToysDomainTrait;

    protected $table = 'toy_categories';

    protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'correlation_id'];

    public function toys(): HasMany
    {
        return $this->hasMany(Toy::class, 'category_id');
    }
}
