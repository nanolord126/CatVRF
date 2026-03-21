<?php

declare(strict_types=1);

namespace App\Domains\Photography\Services;

use App\Domains\Photography\Models\PhotoGallery;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class GalleryService
{
	public function createGallery(array $data): PhotoGallery
	{
		return DB::transaction(function () use ($data) {
			$correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

			$gallery = PhotoGallery::create([
				'uuid' => Str::uuid(),
				'tenant_id' => $data['tenant_id'],
				'photographer_id' => $data['photographer_id'],
				'title' => $data['title'],
				'description' => $data['description'] ?? null,
				'gallery_type' => $data['gallery_type'],
				'photos_json' => $data['photos_json'] ?? [],
				'photo_count' => count($data['photos_json'] ?? []),
				'is_public' => $data['is_public'] ?? true,
				'correlation_id' => $correlationId,
			]);

			Log::channel('audit')->info('Photography: Gallery created', [
				'gallery_id' => $gallery->id,
				'photographer_id' => $data['photographer_id'],
				'correlation_id' => $correlationId,
			]);

			return $gallery;
		});
	}

	public function updateGallery(PhotoGallery $gallery, array $data): PhotoGallery
	{
		return DB::transaction(function () use ($gallery, $data) {
			$gallery->update($data);

			Log::channel('audit')->info('Photography: Gallery updated', [
				'gallery_id' => $gallery->id,
				'correlation_id' => $gallery->correlation_id,
			]);

			return $gallery;
		});
	}
}
