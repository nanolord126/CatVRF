<?php

declare(strict_types=1);


namespace App\Domains\Auto\CarSales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * B2BAutoStorefront
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BAutoStorefront extends Model
{
	use SoftDeletes;

	protected $table = 'b2b_auto_storefronts';

	protected $fillable = [
		'uuid', 'tenant_id', 'company_name', 'inn', 'description',
		'auto_brands', 'wholesale_discount', 'min_order_amount', 'is_verified',
		'is_active', 'correlation_id', 'tags'
	];

	protected $casts = [
		'auto_brands' => 'json',
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
		return $this->hasMany(B2BAutoOrder::class, 'b2b_auto_storefront_id');
	}
}
