<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class B2BRealEstateOrder extends Model
{
	use SoftDeletes;

	protected $table = 'b2b_real_estate_orders';

	protected $fillable = [
		'uuid', 'tenant_id', 'b2b_real_estate_storefront_id', 'order_number',
		'company_contact_person', 'company_phone', 'properties_json', 'total_amount',
		'commission_amount', 'discount_amount', 'status', 'expected_completion_at',
		'notes', 'correlation_id', 'tags'
	];

	protected $casts = [
		'properties_json' => 'json',
		'tags' => 'json',
		'total_amount' => 'decimal:2',
		'commission_amount' => 'decimal:2',
		'discount_amount' => 'decimal:2',
		'expected_completion_at' => 'datetime',
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
		return $this->belongsTo(B2BRealEstateStorefront::class, 'b2b_real_estate_storefront_id');
	}
}
