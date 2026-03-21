<?php

declare(strict_types=1);

namespace App\Domains\Photography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $b2b_photo_storefront_id
 * @property int $photographer_id
 * @property string $order_number
 * @property string $company_contact_person
 * @property string $company_phone
 * @property string $datetime_start
 * @property int $duration_hours
 * @property float $total_amount
 * @property float $commission_amount
 * @property string $status
 */
final class B2BPhotoOrder extends Model
{
	use SoftDeletes;

	protected $table = 'b2b_photo_orders';

	protected $fillable = [
		'uuid', 'tenant_id', 'b2b_photo_storefront_id', 'photographer_id', 'order_number',
		'company_contact_person', 'company_phone', 'datetime_start', 'duration_hours',
		'total_amount', 'commission_amount', 'status', 'notes', 'correlation_id', 'tags'
	];

	protected $casts = [
		'datetime_start' => 'datetime',
		'total_amount' => 'decimal:2',
		'commission_amount' => 'decimal:2',
		'tags' => 'json',
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
		return $this->belongsTo(B2BPhotoStorefront::class, 'b2b_photo_storefront_id');
	}

	public function photographer(): BelongsTo
	{
		return $this->belongsTo(Photographer::class, 'photographer_id');
	}
}
