declare(strict_types=1);

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
 * @property int $photo_studio_id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property float $price
 * @property int $current_stock
 */
final class PhotoProduct extends Model
{
	use SoftDeletes;

	protected $table = 'photo_products';

	protected $fillable = [
		'uuid', 'tenant_id', 'photo_studio_id', 'name', 'description', 'type',
		'price', 'current_stock', 'is_active', 'correlation_id', 'tags'
	];

	protected $casts = [
		'tags' => 'json',
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
}
