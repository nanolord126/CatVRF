<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

final class Filter extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;

    protected $fillable = ['tenant_id', 'vertical', 'vertical_id', 'category_id', 'name', 'type', 'unit'];

    public function values(): HasMany { return $this->hasMany(FilterValue::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}








