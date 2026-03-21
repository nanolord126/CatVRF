<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class B2BRealEstateStorefront extends Model
{
	use SoftDeletes;

	protected $table = 'b2b_real_estate_storefronts';

	protected $fillable = [
		'uuid', 'tenant_id', 'company_name', 'inn', 'description',
		'property_types', 'wholesale_discount', 'min_order_amount', 'is_verified',
		'is_active', 'correlation_id', 'tags'
	];

	protected $casts = [
		'property_types' => 'json',
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
		return $this->hasMany(B2BRealEstateOrder::class, 'b2b_real_estate_storefront_id');
	}
}
