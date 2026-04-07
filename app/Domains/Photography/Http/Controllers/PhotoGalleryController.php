<?php declare(strict_types=1);

namespace App\Domains\Photography\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class PhotoGalleryController extends Controller
{

    public function __construct(private readonly GalleryService $galleryService,
    		private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

    	public function show(int $id): JsonResponse
    	{
    		try {
    			$photographer = Photographer::with('galleries')->findOrFail($id);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $photographer,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
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

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $galleries,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
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

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $galleries,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
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

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $gallery,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
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

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $gallery->photos_json,
    				'count' => $gallery->photo_count,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function store(Request $request): JsonResponse
    	{
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

    		try {
    			$this->authorize('create', PhotoGallery::class);

    			$validated = $request->validate([
    				'title' => 'required|string|max:255',
    				'description' => 'nullable|string',
    				'gallery_type' => 'required|in:portfolio,session,showcase',
    				'is_public' => 'boolean',
    			]);

    			$gallery = $this->galleryService->createGallery(
    				array_merge($validated, [
    					'tenant_id' => $request->user()->tenant_id,
    					'photographer_id' => $request->user()?->id,
    					'correlation_id' => $correlationId,
    				])
    			);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $gallery,
    				'correlation_id' => $correlationId,
    			], 201);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function update(int $id, Request $request): JsonResponse
    	{
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

    		try {
    			$gallery = PhotoGallery::findOrFail($id);

    			$validated = $request->validate([
    				'title' => 'sometimes|string|max:255',
    				'description' => 'sometimes|nullable|string',
    				'is_public' => 'sometimes|boolean',
    			]);

    			$this->galleryService->updateGallery($gallery, $validated);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'message' => 'Галерея обновлена',
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function destroy(int $id): JsonResponse
    	{
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

    		try {
    			$gallery = PhotoGallery::findOrFail($id);

    			$this->db->transaction(function () use ($gallery) {
    				$gallery->delete();

    				$this->logger->info('Photography: Gallery deleted', [
    					'gallery_id' => $gallery->id,
    					'correlation_id' => Str::uuid(),
    				]);
    			});

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'message' => 'Галерея удалена',
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
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

    			$this->db->transaction(function () use ($gallery, $validated) {
    				$photos = array_merge($gallery->photos_json ?? [], $validated['photos']);
    				$gallery->update([
    					'photos_json' => $photos,
    					'photo_count' => count($photos),
    				]);

    				$this->logger->info('Photography: Photos added to gallery', [
    					'gallery_id' => $gallery->id,
    					'count' => count($validated['photos']),
    					'correlation_id' => Str::uuid(),
    				]);
    			});

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'message' => 'Фото добавлены',
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
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

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $photographers,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}
}
