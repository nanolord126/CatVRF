<?php

declare(strict_types=1);


namespace App\Domains\Photography\Models;

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
 * @property array|null $corporate_packages
 * @property float|null $corporate_rate
 * @property int $min_booking_hours
 * @property bool $is_verified
 */
final class B2BPhotoStorefront extends Model
{
	use SoftDeletes;

	protected $table = 'b2b_photo_storefronts';

	protected $fillable = [
		'uuid', 'tenant_id', 'business_group_id', 'company_name', 'inn', 'description',
		'corporate_packages', 'corporate_rate', 'min_booking_hours', 'is_verified',
		'is_active', 'correlation_id', 'tags'
	];

	protected $casts = [
		'corporate_packages' => 'json',
		'tags' => 'json',
		'is_verified' => 'boolean',
		'is_active' => 'boolean',
		'corporate_rate' => 'decimal:2',
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
		return $this->hasMany(B2BPhotoOrder::class, 'b2b_photo_storefront_id');
	}
}
