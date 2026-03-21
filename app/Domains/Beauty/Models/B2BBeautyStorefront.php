<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

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
 * @property array|null $wholesale_packages
 * @property float $wholesale_discount
 * @property int $min_order_amount
 * @property bool $is_verified
 */
final class B2BBeautyStorefront extends Model
{
	use SoftDeletes;

	protected $table = 'b2b_beauty_storefronts';

	protected $fillable = [
		'uuid', 'tenant_id', 'business_group_id', 'company_name', 'inn', 'description',
		'wholesale_packages', 'wholesale_discount', 'min_order_amount', 'is_verified',
		'is_active', 'correlation_id', 'tags'
	];

	protected $casts = [
		'wholesale_packages' => 'json',
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
		return $this->hasMany(B2BBeautyOrder::class, 'b2b_beauty_storefront_id');
	}
}
