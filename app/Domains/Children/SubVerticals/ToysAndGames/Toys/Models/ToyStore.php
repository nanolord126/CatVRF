<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ToyStore Model (L1)
 * Represents a logical or physical inventory hub.
 */
final class ToyStore extends Model
{
    use TenantScoped;
    use ToysDomainTrait;

    protected $table = 'toy_stores';

    protected $fillable = ['uuid', 'tenant_id', 'name', 'location', 'metadata', 'correlation_id'];

    protected $casts = ['metadata' => 'json'];

    public function toys(): HasMany
    {
        return $this->hasMany(Toy::class, 'store_id');
    }
}
