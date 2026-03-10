<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

final class Brand extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;

    protected $fillable = ['tenant_id', 'name', 'slug', 'logo', 'country', 'description', 'is_platform_partner'];

    public function categories(): BelongsToMany { return $this->belongsToMany(Category::class); }
    public function products(): HasMany { return $this->hasMany(\App\Models\B2B\B2BProduct::class); }
}








