<?php declare(strict_types=1);

namespace App\Domains\Books\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Book extends Model
{
    use SoftDeletes;

    protected $table = 'books';

    protected $fillable = [
        'uuid', 'tenant_id', 'business_group_id', 'name', 'author', 'sku', 'price',
        'rating', 'description', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'tags' => 'json',
        'price' => 'integer',
        'rating' => 'float',
    ];

    public function booted(): void
    {
        $this->addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', filament()?->getTenant()?->id ?? null));
    }
}
