<?php

declare(strict_types=1);

namespace App\Domains\Photography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $name
 * @property string|null $description
 * @property string $address
 * @property string $phone
 * @property string|null $email
 * @property array $studio_types
 * @property array|null $schedule_json
 * @property float $rating
 * @property int $review_count
 * @property bool $is_verified
 * @property bool $is_active
 * @property string|null $correlation_id
 */
final class PhotoStudio extends Model
{
	use SoftDeletes;

	protected $table = 'photo_studios';

	protected $fillable = [
		'uuid', 'tenant_id', 'business_group_id', 'name', 'description', 'address',
		'geo_point', 'phone', 'email', 'studio_types', 'schedule_json', 'rating',
		'review_count', 'is_verified', 'is_active', 'correlation_id', 'tags'
	];

	protected $hidden = [];

	protected $casts = [
		'studio_types' => 'json',
		'schedule_json' => 'json',
		'tags' => 'json',
		'is_verified' => 'boolean',
		'is_active' => 'boolean',
		'rating' => 'float',
	];

	protected static function booted(): void
	{
		static::addGlobalScope('tenant', function ($query) {
			if (auth()->check() && auth()->user()->tenant_id) {
				$query->where('tenant_id', auth()->user()->tenant_id);
			}
		});
	}

	public function photographers(): HasMany
	{
		return $this->hasMany(Photographer::class, 'photo_studio_id');
	}

	public function packages(): HasMany
	{
		return $this->hasMany(PhotoPackage::class, 'photo_studio_id');
	}

	public function sessions(): HasMany
	{
		return $this->hasMany(Photo$this->session->class, 'photo_studio_id');
	}

	public function products(): HasMany
	{
		return $this->hasMany(PhotoProduct::class, 'photo_studio_id');
	}

	public function reviews(): HasMany
	{
		return $this->hasMany(PhotoReview::class, 'photo_studio_id');
	}
}
