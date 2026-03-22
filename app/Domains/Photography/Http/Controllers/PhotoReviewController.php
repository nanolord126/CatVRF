<?php

declare(strict_types=1);

namespace App\Domains\Photography\Http\Controllers;

use App\Domains\Photography\Models\PhotoReview;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PhotoReviewController
{
	public function __construct(
		private readonly FraudControlService $fraudControlService,
	) {}

	public function index(): JsonResponse
	{
		try {
			$reviews = PhotoReview::with('photographer', 'studio')
				->latest()
				->paginate(20);

			return response()->json([
				'success' => true,
				'data' => $reviews,
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

	public function show(int $id): JsonResponse
	{
		try {
			$review = PhotoReview::with('photographer', 'studio')->findOrFail($id);

			return response()->json([
				'success' => true,
				'data' => $review,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Отзыв не найден',
				'correlation_id' => Str::uuid(),
			], 404);
		}
	}

	public function store(int $sessionId, Request $request): JsonResponse
	{
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

		try {
			$this->authorize('create', PhotoReview::class);

			$validated = $request->validate([
				'rating' => 'required|integer|min:1|max:5',
				'comment' => 'nullable|string|max:1000',
			]);

			DB::transaction(function () use ($sessionId, $validated, $correlationId) {
				$session = \App\Domains\Photography\Models\PhotoSession::findOrFail($sessionId);

				$review = PhotoReview::create([
					'uuid' => Str::uuid(),
					'tenant_id' => auth()->user()->tenant_id,
					'photo_studio_id' => $session->photo_studio_id,
					'photographer_id' => $session->photographer_id,
					'photo_session_id' => $sessionId,
					'user_id' => auth()->id(),
					'rating' => $validated['rating'],
					'comment' => $validated['comment'] ?? null,
					'is_verified_purchase' => true,
					'correlation_id' => $correlationId,
				]);

				Log::channel('audit')->info('Photography: Review created', [
					'review_id' => $review->id,
					'rating' => $validated['rating'],
					'correlation_id' => $correlationId,
				]);
			});

			return response()->json([
				'success' => true,
				'message' => 'Отзыв создан',
				'correlation_id' => $correlationId,
			], 201);
		} catch (\Exception $e) {
			Log::channel('audit')->error('Photography: Review creation failed', [
				'error' => $e->getMessage(),
				'correlation_id' => Str::uuid(),
			]);
			return response()->json([
				'success' => false,
				'message' => 'Ошибка при создании отзыва',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}

	public function update(int $id, Request $request): JsonResponse
	{
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

		try {
			$review = PhotoReview::findOrFail($id);
			$this->authorize('update', $review);

			$validated = $request->validate([
				'rating' => 'sometimes|integer|min:1|max:5',
				'comment' => 'sometimes|nullable|string|max:1000',
			]);

			$review->update($validated);

			Log::channel('audit')->info('Photography: Review updated', [
				'review_id' => $id,
				'correlation_id' => Str::uuid(),
			]);

			return response()->json([
				'success' => true,
				'message' => 'Отзыв обновлен',
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
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

		try {
			$review = PhotoReview::findOrFail($id);
			$this->authorize('delete', $review);

			$review->delete();

			Log::channel('audit')->info('Photography: Review deleted', [
				'review_id' => $id,
				'correlation_id' => Str::uuid(),
			]);

			return response()->json([
				'success' => true,
				'message' => 'Отзыв удален',
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

	public function photographerReviews(int $id): JsonResponse
	{
		try {
			$reviews = PhotoReview::where('photographer_id', $id)
				->latest()
				->paginate(10);

			return response()->json([
				'success' => true,
				'data' => $reviews,
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

	public function studioReviews(int $id): JsonResponse
	{
		try {
			$reviews = PhotoReview::where('photo_studio_id', $id)
				->latest()
				->paginate(10);

			return response()->json([
				'success' => true,
				'data' => $reviews,
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

	public function markHelpful(int $id): JsonResponse
	{
		try {
			$review = PhotoReview::findOrFail($id);

			DB::transaction(function () use ($review) {
				$review->increment('helpful_count');

				Log::channel('audit')->info('Photography: Review marked helpful', [
					'review_id' => $review->id,
					'correlation_id' => Str::uuid(),
				]);
			});

			return response()->json([
				'success' => true,
				'message' => 'Спасибо за отзыв',
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
