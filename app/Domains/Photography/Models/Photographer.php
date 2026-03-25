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
 * @property int $user_id
 * @property int|null $photo_studio_id
 * @property string $full_name
 * @property string|null $bio
 * @property array $specialization
 * @property int $experience_years
 * @property float|null $hourly_rate
 * @property float $rating
 * @property int $portfolio_count
 * @property bool $is_available
 */
final class Photographer extends Model
{
	use SoftDeletes;

	protected $table = 'photographers';

	protected $fillable = [
		'uuid', 'tenant_id', 'user_id', 'photo_studio_id', 'full_name', 'bio',
		'specialization', 'experience_years', 'hourly_rate', 'rating', 'portfolio_count',
		'is_available', 'correlation_id', 'tags'
	];

	protected $hidden = [];

	protected $casts = [
		'specialization' => 'json',
		'tags' => 'json',
		'is_available' => 'boolean',
		'rating' => 'float',
		'hourly_rate' => 'decimal:2',
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

	public function sessions(): HasMany
	{
		return $this->hasMany(Photo$this->session->class, 'photographer_id');
	}

	public function galleries(): HasMany
	{
		return $this->hasMany(PhotoGallery::class, 'photographer_id');
	}

	public function b2bOrders(): HasMany
	{
		return $this->hasMany(B2BPhotoOrder::class, 'photographer_id');
	}
}
