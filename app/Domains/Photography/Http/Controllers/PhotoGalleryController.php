<?php

declare(strict_types=1);

namespace App\Domains\Photography\Http\Controllers;

use App\Domains\Photography\Models\PhotoGallery;
use App\Domains\Photography\Models\Photographer;
use App\Domains\Photography\Services\GalleryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PhotoGalleryController
{
	public function __construct(
		private readonly GalleryService $galleryService
	) {}

	public function show(int $id): JsonResponse
	{
		try {
			$photographer = Photographer::with('galleries')->findOrFail($id);

			return response()->json([
				'success' => true,
				'data' => $photographer,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Фотограф не найден',
				'correlation_id' => Str::uuid(),
			], 404);
		}
	}

	public function portfolio(int $id): JsonResponse
	{
		try {
			$galleries = PhotoGallery::where('photographer_id', $id)
				->where('is_public', true)
				->latest()
				->get();

			return response()->json([
				'success' => true,
				'data' => $galleries,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Ошибка',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}

	public function galleries(int $id): JsonResponse
	{
		try {
			$galleries = PhotoGallery::where('photographer_id', $id)
				->latest()
				->paginate(20);

			return response()->json([
				'success' => true,
				'data' => $galleries,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Ошибка',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}

	public function showGallery(int $id): JsonResponse
	{
		try {
			$gallery = PhotoGallery::findOrFail($id);

			$gallery->increment('view_count');

			return response()->json([
				'success' => true,
				'data' => $gallery,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Галерея не найдена',
				'correlation_id' => Str::uuid(),
			], 404);
		}
	}

	public function photos(int $id): JsonResponse
	{
		try {
			$gallery = PhotoGallery::findOrFail($id);

			return response()->json([
				'success' => true,
				'data' => $gallery->photos_json,
				'count' => $gallery->photo_count,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Ошибка',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}

	public function store(Request $request): JsonResponse
	{
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

		try {
			$this->authorize('create', PhotoGallery::class);

			$validated = $request->validate([
				'title' => 'required|string|max:255',
				'description' => 'nullable|string',
				'gallery_type' => 'required|in:portfolio,session,showcase',
				'is_public' => 'boolean',
			]);

			$correlationId = Str::uuid()->toString();

			$gallery = $this->galleryService->createGallery(
				array_merge($validated, [
					'tenant_id' => auth()->user()->tenant_id,
					'photographer_id' => auth()->id(),
					'correlation_id' => $correlationId,
				])
			);

			return response()->json([
				'success' => true,
				'data' => $gallery,
				'correlation_id' => $correlationId,
			], 201);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Ошибка',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}

	public function update(int $id, Request $request): JsonResponse
	{
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

		try {
			$gallery = PhotoGallery::findOrFail($id);

			$validated = $request->validate([
				'title' => 'sometimes|string|max:255',
				'description' => 'sometimes|nullable|string',
				'is_public' => 'sometimes|boolean',
			]);

			$this->galleryService->updateGallery($gallery, $validated);

			return response()->json([
				'success' => true,
				'message' => 'Галерея обновлена',
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Ошибка',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}

	public function destroy(int $id): JsonResponse
	{
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

		try {
			$gallery = PhotoGallery::findOrFail($id);

			DB::transaction(function () use ($gallery) {
				$gallery->delete();

				Log::channel('audit')->info('Photography: Gallery deleted', [
					'gallery_id' => $gallery->id,
					'correlation_id' => Str::uuid(),
				]);
			});

			return response()->json([
				'success' => true,
				'message' => 'Галерея удалена',
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Ошибка',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}

	public function addPhotos(int $id, Request $request): JsonResponse
	{
		try {
			$gallery = PhotoGallery::findOrFail($id);

			$validated = $request->validate([
				'photos' => 'required|array',
				'photos.*' => 'url',
			]);

			DB::transaction(function () use ($gallery, $validated) {
				$photos = array_merge($gallery->photos_json ?? [], $validated['photos']);
				$gallery->update([
					'photos_json' => $photos,
					'photo_count' => count($photos),
				]);

				Log::channel('audit')->info('Photography: Photos added to gallery', [
					'gallery_id' => $gallery->id,
					'count' => count($validated['photos']),
					'correlation_id' => Str::uuid(),
				]);
			});

			return response()->json([
				'success' => true,
				'message' => 'Фото добавлены',
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Ошибка',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}

	public function topPhotographers(): JsonResponse
	{
		try {
			$photographers = Photographer::orderByDesc('rating')
				->limit(10)
				->get();

			return response()->json([
				'success' => true,
				'data' => $photographers,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Ошибка',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}
}
