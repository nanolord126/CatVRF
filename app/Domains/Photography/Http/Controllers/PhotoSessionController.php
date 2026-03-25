<?php

declare(strict_types=1);

namespace App\Domains\Photography\Http\Controllers;

use App\Domains\Photography\Models\PhotoSession;
use App\Domains\Photography\Services\SessionService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PhotoSessionController
{
	public function __construct(
		private readonly SessionService $sessionService,
		private readonly FraudControlService $fraudControlService,
	) {}

	public function store(Request $request): JsonResponse
	{
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

		try {
			$this->authorize('create', Photo$this->session->class);

			$validated = $request->validate([
				'photo_studio_id' => 'required|exists:photo_studios,id',
				'photographer_id' => 'required|exists:photographers,id',
				'photo_package_id' => 'required|exists:photo_packages,id',
				'datetime_start' => 'required|date',
				'datetime_end' => 'required|date|after:datetime_start',
				'total_amount' => 'required|numeric|min:1',
			]);

			$session = $this->sessionService->createSession(
				array_merge($validated, [
					'user_id' => auth()->id(),
					'tenant_id' => auth()->user()->tenant_id,
					'correlation_id' => $correlationId,
				])
			);

			return response()->json([
				'success' => true,
				'data' => $session,
				'correlation_id' => $correlationId,
			], 201);
		} catch (\Exception $e) {
			$this->log->channel('audit')->error('Photography: Session creation failed', [
				'error' => $e->getMessage(),
				'correlation_id' => Str::uuid(),
			]);
			return response()->json([
				'success' => false,
				'message' => 'Ошибка при создании сессии',
				'correlation_id' => Str::uuid(),
			], 500);
		}
	}

	public function show(int $id): JsonResponse
	{
		try {
			$session = Photo$this->session->findOrFail($id);
			$this->authorize('view', $session);

			return response()->json([
				'success' => true,
				'data' => $session,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Сессия не найдена',
				'correlation_id' => Str::uuid(),
			], 404);
		}
	}

	public function mySessions(): JsonResponse
	{
		try {
			$sessions = Photo$this->session->where('user_id', auth()->id())
				->latest()
				->paginate(20);

			return response()->json([
				'success' => true,
				'data' => $sessions,
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

	public function updateStatus(int $id): JsonResponse
	{
		try {
			$session = Photo$this->session->findOrFail($id);
			$this->authorize('update', $session);

			$status = request()->validate(['status' => 'required|in:pending,confirmed,completed,cancelled'])['status'];

			$this->sessionService->updateSessionStatus($session, $status);

			return response()->json([
				'success' => true,
				'message' => 'Статус обновлен',
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

	public function cancel(int $id): JsonResponse
	{
		try {
			$session = Photo$this->session->findOrFail($id);
			$this->authorize('cancel', $session);

			$this->sessionService->cancelSession($session);

			return response()->json([
				'success' => true,
				'message' => 'Сессия отменена',
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Отмена невозможна',
				'correlation_id' => Str::uuid(),
			], 403);
		}
	}

	public function pendingSessions(): JsonResponse
	{
		try {
			$sessions = Photo$this->session->where('status', 'pending')->paginate(20);

			return response()->json([
				'success' => true,
				'data' => $sessions,
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
