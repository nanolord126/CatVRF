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
 * @property int $photographer_id
 * @property string $title
 * @property string|null $description
 * @property string $gallery_type
 * @property array|null $photos_json
 * @property int $photo_count
 * @property bool $is_public
 * @property int $view_count
 */
final class PhotoGallery extends Model
{
	use SoftDeletes;

	protected $table = 'photo_galleries';

	protected $fillable = [
		'uuid', 'tenant_id', 'photographer_id', 'title', 'description', 'gallery_type',
		'photos_json', 'photo_count', 'is_public', 'view_count', 'correlation_id', 'tags'
	];

	protected $casts = [
		'photos_json' => 'json',
		'tags' => 'json',
		'is_public' => 'boolean',
	];

	protected static function booted(): void
	{
		static::addGlobalScope('tenant', function ($query) {
			if (auth()->check() && auth()->user()->tenant_id) {
				$query->where('tenant_id', auth()->user()->tenant_id);
			}
		});
	}

	public function photographer(): BelongsTo
	{
		return $this->belongsTo(Photographer::class, 'photographer_id');
	}
}
