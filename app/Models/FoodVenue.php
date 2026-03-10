<?php
namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class FoodVenue extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = ['name', 'sub_type', 'cuisine_type', 'tenant_id'];
    protected static function booted() {
        static::creating(function ($model) {
            if (tenant()) $model->tenant_id = tenant()->id;
        });
    }
}









