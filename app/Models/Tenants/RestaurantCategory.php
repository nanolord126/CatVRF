<?php

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;

class RestaurantCategory extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $table = 'restaurant_categories';
    protected $fillable = ['name'];

    public function dishes()
    {
        return $this->hasMany(RestaurantDish::class, 'category_id');
    }
}








