<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class BeautyProduct extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;

    protected $fillable = ['tenant_id', 'name', 'type', 'price', 'stock', 'images'];
    protected $casts = ['images' => 'array', 'price' => 'decimal:2'];
}








