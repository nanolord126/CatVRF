<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $b2b_beauty_storefront_id
 * @property string $order_number
 * @property string $company_contact_person
 * @property string $company_phone
 * @property array|null $items_json
 * @property float $total_amount
 * @property float $commission_amount
 * @property float $discount_amount
 * @property string $status
 */
final class B2BBeautyOrder extends Model
{
	use SoftDeletes;

	protected $table = 'b2b_beauty_orders';

	protected $fillable = [
		'uuid', 'tenant_id', 'b2b_beauty_storefront_id', 'order_number',
		'company_contact_person', 'company_phone', 'items_json', 'total_amount',
		'commission_amount', 'discount_amount', 'status', 'expected_delivery_at',
		'notes', 'correlation_id', 'tags'
	];

	protected $casts = [
		'items_json' => 'json',
		'tags' => 'json',
		'total_amount' => 'decimal:2',
		'commission_amount' => 'decimal:2',
		'discount_amount' => 'decimal:2',
		'expected_delivery_at' => 'datetime',
	];

	protected static function booted(): void
	{
		static::addGlobalScope('tenant', function ($query) {
			if (auth()->check() && auth()->user()->tenant_id) {
				$query->where('tenant_id', auth()->user()->tenant_id);
			}
		});
	}

	public function storefront(): BelongsTo
	{
		return $this->belongsTo(B2BBeautyStorefront::class, 'b2b_beauty_storefront_id');
	}
}
