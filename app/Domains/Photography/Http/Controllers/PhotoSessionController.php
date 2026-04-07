<?php declare(strict_types=1);

namespace App\Domains\Photography\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class PhotoSessionController extends Controller
{

    public function __construct(
    		private readonly SessionService $sessionService,
    		private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

    	public function store(Request $request): JsonResponse
    	{
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

    		try {
    			$this->authorize('create', PhotoSession::class);

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
    					'user_id' => $request->user()?->id,
    					'tenant_id' => $request->user()->tenant_id,
    					'correlation_id' => $correlationId,
    				])
    			);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $session,
    				'correlation_id' => $correlationId,
    			], 201);
    		} catch (\Throwable $e) {
    			$this->logger->error('Photography: Session creation failed', [
    				'error' => $e->getMessage(),
    				'correlation_id' => Str::uuid(),
    			]);
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка при создании сессии',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function show(int $id): JsonResponse
    	{
    		try {
    			$session = PhotoSession->findOrFail($id);
    			$this->authorize('view', $session);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $session,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Сессия не найдена',
    				'correlation_id' => Str::uuid(),
    			], 404);
    		}
    	}

    	public function mySessions(): JsonResponse
    	{
    		try {
    			$sessions = PhotoSession->where('user_id', $request->user()?->id)
    				->latest()
    				->paginate(20);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $sessions,
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

    	public function updateStatus(int $id): JsonResponse
    	{
    		try {
    			$session = PhotoSession->findOrFail($id);
    			$this->authorize('update', $session);

    			$status = $request->validate(['status' => 'required|in:pending,confirmed,completed,cancelled'])['status'];

    			$this->sessionService->updateSessionStatus($session, $status);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'message' => 'Статус обновлен',
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

    	public function cancel(int $id): JsonResponse
    	{
    		try {
    			$session = PhotoSession->findOrFail($id);
    			$this->authorize('cancel', $session);

    			$this->sessionService->cancelSession($session);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'message' => 'Сессия отменена',
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Отмена невозможна',
    				'correlation_id' => Str::uuid(),
    			], 403);
    		}
    	}

    	public function pendingSessions(): JsonResponse
    	{
    		try {
    			$sessions = PhotoSession->where('status', 'pending')->paginate(20);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $sessions,
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
