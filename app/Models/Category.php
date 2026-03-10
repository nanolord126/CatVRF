<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

final class Category extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;

    protected $fillable = ['tenant_id', 'parent_id', 'vertical', 'slug', 'name', 'icon', 'description', 'order', 'is_active'];

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('order'); }
    public function brands(): BelongsToMany { return $this->belongsToMany(Brand::class); }
    public function filters(): HasMany { return $this->hasMany(Filter::class); }
}








