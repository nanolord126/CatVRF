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
 * @property int $photographer_id
 * @property int $photo_session_id
 * @property int $user_id
 * @property int $rating
 * @property string|null $comment
 * @property bool $is_verified_purchase
 * @property int $helpful_count
 */
final class PhotoReview extends Model
{
	use SoftDeletes;

	protected $table = 'photo_reviews';

	protected $fillable = [
		'uuid', 'tenant_id', 'photo_studio_id', 'photographer_id', 'photo_session_id',
		'user_id', 'rating', 'comment', 'is_verified_purchase', 'helpful_count',
		'correlation_id'
	];

	protected $casts = [
		'is_verified_purchase' => 'boolean',
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

	public function session(): BelongsTo
	{
		return $this->belongsTo(Photo$this->session->class, 'photo_session_id');
	}
}
