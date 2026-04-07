<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Collaboration;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class CollaborationController extends Controller
{

    public function __construct(
            private readonly CollaborationService $collaboration,
            private readonly ConflictResolutionService $conflicts,
            private readonly TeamPresenceService $presence,
            private readonly LogManager $logger,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {
            // PRODUCTION-READY 2026 CANON: Middleware для Team Collaboration
             // Командная работа требует авторизации
             // 500 запросов/час (real-time операции)
             // Tenant scoping обязателен
            $this->middleware('role:admin|manager|team_lead', ['only' => ['resolveConflict', 'removeUser']]);
        }
        /**
         * Начинает сессию редактирования
         */
        public function startEditingSession(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'document_type' => 'required|string|max:50',
                    'document_id' => 'required|integer|min:1',
                ]);
                $session = $this->collaboration->startEditingSession(
                    userId: $this->guard->id(),
                    tenantId: filament()->getTenant()->id,
                    documentType: $validated['document_type'],
                    documentId: $validated['document_id'],
                    correlationId: $correlationId
                );
                // Регистрируем присутствие
                $this->presence->registerPresence(
                    userId: $this->guard->id(),
                    tenantId: filament()->getTenant()->id,
                    documentType: $validated['document_type'],
                    documentId: $validated['document_id'],
                    correlationId: $correlationId
                );
                // Отправляем событие
                broadcast(new EditStarted(
                    userId: $this->guard->id(),
                    tenantId: filament()->getTenant()->id,
                    documentType: $validated['document_type'],
                    documentId: $validated['document_id'],
                    userName: $this->guard->user()->name,
                    correlationId: $correlationId
                ));
                $this->logger->channel('audit')->info('Editing session started', [
                    'correlation_id' => $correlationId,
                    'user_id' => $this->guard->id(),
                    'document' => "{$validated['document_type']}:{$validated['document_id']}",
                ]);
                return $this->response->json([
                    'success' => true,
                    'session' => $session,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to start editing session', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'error' => 'Failed to start editing session',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Завершает сессию редактирования
         */
        public function endEditingSession(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'session_id' => 'required|string|uuid',
                    'document_type' => 'required|string|max:50',
                    'document_id' => 'required|integer|min:1',
                ]);
                $this->collaboration->endEditingSession(
                    sessionId: $validated['session_id'],
                    tenantId: filament()->getTenant()->id,
                    documentType: $validated['document_type'],
                    documentId: $validated['document_id'],
                    correlationId: $correlationId
                );
                // Удаляем присутствие
                $this->presence->unregisterPresence(
                    userId: $this->guard->id(),
                    tenantId: filament()->getTenant()->id,
                    documentType: $validated['document_type'],
                    documentId: $validated['document_id'],
                    correlationId: $correlationId
                );
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to end editing session', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'error' => 'Failed to end editing session',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Получает активных редакторов документа
         */
        public function getActiveEditors(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'document_type' => 'required|string|max:50',
                    'document_id' => 'required|integer|min:1',
                ]);
                $editors = $this->collaboration->getActiveEditors(
                    tenantId: filament()->getTenant()->id,
                    documentType: $validated['document_type'],
                    documentId: $validated['document_id']
                );
                return $this->response->json([
                    'editors' => $editors,
                    'count' => $editors->count(),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to get active editors', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'error' => 'Failed to get active editors',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Отправляет изменение
         */
        public function submitEdit(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'document_type' => 'required|string|max:50',
                    'document_id' => 'required|integer|min:1',
                    'operation' => 'required|string|in:insert,delete,replace,update',
                    'content' => 'nullable|string',
                    'position' => 'nullable|array',
                    'element_id' => 'nullable|string|max:255',
                ]);
                // Регистрируем изменение
                $edit = $this->conflicts->recordEdit(
                    sessionId: Str::uuid()->toString(),
                    userId: $this->guard->id(),
                    documentType: $validated['document_type'],
                    documentId: $validated['document_id'],
                    tenantId: filament()->getTenant()->id,
                    editData: $validated,
                    correlationId: $correlationId
                );
                // Отправляем событие
                broadcast(new EditCompleted(
                    userId: $this->guard->id(),
                    tenantId: filament()->getTenant()->id,
                    documentType: $validated['document_type'],
                    documentId: $validated['document_id'],
                    userName: $this->guard->user()->name,
                    editData: $edit,
                    correlationId: $correlationId
                ));
                return $this->response->json([
                    'success' => true,
                    'edit' => $edit,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to submit edit', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'error' => 'Failed to submit edit',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Получает присутствующих пользователей
         */
        public function getTeamPresence(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'document_type' => 'required|string|max:50',
                    'document_id' => 'required|integer|min:1',
                ]);
                $presence = $this->presence->getPresenceList(
                    tenantId: filament()->getTenant()->id,
                    documentType: $validated['document_type'],
                    documentId: $validated['document_id']
                );
                return $this->response->json([
                    'presence' => $presence,
                    'count' => count($presence),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to get team presence', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'error' => 'Failed to get team presence',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
