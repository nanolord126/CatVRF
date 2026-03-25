declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * B2BHotelOrder
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BHotelOrder extends Model
{
	use SoftDeletes;

	protected $table = 'b2b_hotel_orders';

	protected $fillable = [
		'uuid', 'tenant_id', 'b2b_hotel_storefront_id', 'order_number',
		'company_contact_person', 'company_phone', 'booking_details', 'total_amount',
		'commission_amount', 'discount_amount', 'status', 'check_in_at', 'check_out_at',
		'notes', 'correlation_id', 'tags'
	];

	protected $casts = [
		'booking_details' => 'json',
		'tags' => 'json',
		'total_amount' => 'decimal:2',
		'commission_amount' => 'decimal:2',
		'discount_amount' => 'decimal:2',
		'check_in_at' => 'datetime',
		'check_out_at' => 'datetime',
	];

	protected static function booted(): void
	{
		static::addGlobalScope('tenant', fn($q) => $q->where('tenant_id', auth()->user()?->tenant_id ?? null));
	}

	public function storefront(): BelongsTo
	{
		return $this->belongsTo(B2BHotelStorefront::class, 'b2b_hotel_storefront_id');
	}
}
