<?php

declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class B2BFoodStorefront extends Model
{
	use SoftDeletes;

	protected $table = 'b2b_food_storefronts';

	protected $fillable = [
		'uuid', 'tenant_id', 'company_name', 'inn', 'description',
		'cuisine_types', 'wholesale_discount', 'min_order_amount', 'is_verified',
		'is_active', 'correlation_id', 'tags'
	];

	protected $casts = [
		'cuisine_types' => 'json',
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
		return $this->hasMany(B2BFoodOrder::class, 'b2b_food_storefront_id');
	}
}
