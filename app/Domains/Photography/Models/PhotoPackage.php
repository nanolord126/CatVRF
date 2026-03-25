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
 * @property int|null $photo_studio_id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property float $price
 * @property int $duration_minutes
 * @property int $photo_count
 * @property int $retouching_days
 * @property bool $includes_prints
 */
final class PhotoPackage extends Model
{
	use SoftDeletes;

	protected $table = 'photo_packages';

	protected $fillable = [
		'uuid', 'tenant_id', 'photo_studio_id', 'name', 'description', 'type',
		'price', 'duration_minutes', 'photo_count', 'retouching_days', 'includes_prints',
		'is_active', 'correlation_id', 'tags'
	];

	protected $casts = [
		'tags' => 'json',
		'includes_prints' => 'boolean',
		'is_active' => 'boolean',
		'price' => 'decimal:2',
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
		return $this->hasMany(Photo$this->session->class, 'photo_package_id');
	}
}
