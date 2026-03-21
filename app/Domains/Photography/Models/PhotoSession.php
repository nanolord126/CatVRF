<?php

declare(strict_types=1);

namespace App\Domains\Photography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $photo_studio_id
 * @property int $photographer_id
 * @property int $photo_package_id
 * @property int $user_id
 * @property string $session_number
 * @property string $datetime_start
 * @property string $datetime_end
 * @property float $total_amount
 * @property float $commission_amount
 * @property string $status
 * @property string $payment_status
 */
final class PhotoSession extends Model
{
	use SoftDeletes;

	protected $table = 'photo_sessions';

	protected $fillable = [
		'uuid', 'tenant_id', 'photo_studio_id', 'photographer_id', 'photo_package_id',
		'user_id', 'session_number', 'datetime_start', 'datetime_end', 'total_amount',
		'commission_amount', 'status', 'payment_status', 'notes', 'correlation_id', 'tags'
	];

	protected $casts = [
		'datetime_start' => 'datetime',
		'datetime_end' => 'datetime',
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

	public function studio(): BelongsTo
	{
		return $this->belongsTo(PhotoStudio::class, 'photo_studio_id');
	}

	public function photographer(): BelongsTo
	{
		return $this->belongsTo(Photographer::class, 'photographer_id');
	}

	public function package(): BelongsTo
	{
		return $this->belongsTo(PhotoPackage::class, 'photo_package_id');
	}

	public function reviews(): HasMany
	{
		return $this->hasMany(PhotoReview::class, 'photo_session_id');
	}
}
