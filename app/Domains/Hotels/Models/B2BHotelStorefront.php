declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * B2BHotelStorefront
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BHotelStorefront extends Model
{
	use SoftDeletes;

	protected $table = 'b2b_hotel_storefronts';

	protected $fillable = [
		'uuid', 'tenant_id', 'company_name', 'inn', 'description',
		'room_types', 'wholesale_discount', 'min_booking_nights', 'is_verified',
		'is_active', 'correlation_id', 'tags'
	];

	protected $casts = [
		'room_types' => 'json',
		'tags' => 'json',
		'is_verified' => 'boolean',
		'is_active' => 'boolean',
		'wholesale_discount' => 'decimal:2',
	];

	protected static function booted(): void
	{
		static::addGlobalScope('tenant', fn($q) => $q->where('tenant_id', auth()->user()?->tenant_id ?? null));
	}

	public function b2bOrders(): HasMany
	{
		return $this->hasMany(B2BHotelOrder::class, 'b2b_hotel_storefront_id');
	}
}
