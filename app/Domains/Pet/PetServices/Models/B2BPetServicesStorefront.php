<?php declare(strict_types=1);

namespace App\Domains\Pet\PetServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $company_name
 * @property string $inn
 * @property string|null $description
 * @property array|null $service_categories
 * @property float $wholesale_discount
 * @property int $min_order_amount
 * @property bool $is_verified
 * @property bool $is_active
 * @property string|null $correlation_id
 * @property array|null $tags
 */
final class B2BPetServicesStorefront extends Model
{
    use SoftDeletes;

    protected $table = 'b2b_pet_services_storefronts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'company_name',
        'inn',
        'description',
        'service_categories',
        'wholesale_discount',
        'min_order_amount',
        'is_verified',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'service_categories' => 'json',
        'tags' => 'json',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'wholesale_discount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    public function b2bOrders(): HasMany
    {
        return $this->hasMany('App\Domains\Pet\PetServices\Models\B2BPetServicesOrder');
    }
}
