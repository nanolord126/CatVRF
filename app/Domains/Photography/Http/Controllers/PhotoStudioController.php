<?php

declare(strict_types=1);

namespace App\Domains\Photography\Http\Controllers;

use App\Domains\Photography\Models\PhotoStudio;
use App\Domains\Photography\Models\Photographer;
use App\Domains\Photography\Models\PhotoPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PhotoStudioController
{
	public function index(): JsonResponse
	{
		try {
			$studios = PhotoStudio::where('is_active', true)
				->with('photographers', 'packages', 'reviews')
				->paginate(20);

			return response()->json([
				'success' => true,
				'data' => $studios,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			$this->log->channel('audit')->error('Photography: Studios list failed', [
				'error' => $e->getMessage(),
			]);
			return response()->json([
				'success' => false,
				'message' => 'Ошибка при загрузке студий',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}

	public function show(int $id): JsonResponse
	{
		try {
			$studio = PhotoStudio::with('photographers', 'packages', 'reviews')
				->findOrFail($id);

			return response()->json([
				'success' => true,
				'data' => $studio,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			$this->log->channel('audit')->error('Photography: Studio show failed', [
				'studio_id' => $id,
				'error' => $e->getMessage(),
			]);
			return response()->json([
				'success' => false,
				'message' => 'Студия не найдена',
				'correlation_id' => Str::uuid(),
			], 404);
		}
	}

	public function packages(int $id): JsonResponse
	{
		try {
			$packages = PhotoPackage::where('photo_studio_id', $id)
				->where('is_active', true)
				->get();

			return response()->json([
				'success' => true,
				'data' => $packages,
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

	public function photographers(int $id): JsonResponse
	{
		try {
			$photographers = Photographer::where('photo_studio_id', $id)
				->where('is_available', true)
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

	public function reviews(int $id): JsonResponse
	{
		try {
			$reviews = PhotoStudio::findOrFail($id)->reviews()
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

	public function search(): JsonResponse
	{
		try {
			$query = request()->get('q');
			$type = request()->get('type');

			$studios = PhotoStudio::where('is_active', true)
				->when($query, fn($q) => $q->where('name', 'like', "%{$query}%"))
				->when($type, fn($q) => $q->whereJsonContains('studio_types', $type))
				->paginate(20);

			return response()->json([
				'success' => true,
				'data' => $studios,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Ошибка поиска',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}

	public function stats(): JsonResponse
	{
		try {
			$this->authorize('viewStats', PhotoStudio::class);

			$stats = [
				'total_studios' => PhotoStudio::count(),
				'verified_studios' => PhotoStudio::where('is_verified', true)->count(),
				'avg_rating' => PhotoStudio::avg('rating'),
				'total_sessions' => \App\Domains\Photography\Models\Photo$this->session->count(),
			];

			return response()->json([
				'success' => true,
				'data' => $stats,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Доступ запрещен',
				'correlation_id' => Str::uuid(),
			], 403);
		}
	}

	public function topStudios(): JsonResponse
	{
		try {
			$studios = PhotoStudio::orderByDesc('rating')
				->limit(10)
				->get();

			return response()->json([
				'success' => true,
				'data' => $studios,
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

	public function verify(int $id): JsonResponse
	{
		try {
			$this->authorize('verify', PhotoStudio::class);

			$this->db->transaction(function () use ($id) {
				$studio = PhotoStudio::findOrFail($id);
				$studio->update(['is_verified' => true]);

				$this->log->channel('audit')->info('Photography: Studio verified', [
					'studio_id' => $id,
					'correlation_id' => Str::uuid(),
				]);
			});

			return response()->json([
				'success' => true,
				'message' => 'Студия верифицирована',
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Ошибка верификации',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}
}
