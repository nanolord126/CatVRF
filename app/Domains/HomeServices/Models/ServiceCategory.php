<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ServiceCategory extends Model
{
    protected $table = 'service_categories';
    protected $fillable = ['tenant_id', 'name', 'description', 'icon', 'tags', 'is_active', 'correlation_id'];
    protected $hidden = [];
    protected $casts = ['tags' => 'collection', 'is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant('id')));
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function serviceListings(): HasMany { return $this->hasMany(ServiceListing::class); }
}
