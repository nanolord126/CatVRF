<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require Segregation of Duties Middleware
 * 
 * Middleware для проверки разделения обязанностей (segregation of duties)
 * Запрещает выполнение критических операций одним и тем же пользователем
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class RequireSegregationOfDuties
{
    public function handle(Request $request, Closure $next, string $operationType): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        // Проверка правил разделения обязанностей для типа операции
        $violations = $this->checkSegregationViolations($user, $operationType, $request);

        if (!empty($violations)) {
            return response()->json([
                'success' => false,
                'message' => 'Segregation of duties violation',
                'violations' => $violations,
            ], 403);
        }

        return $next($request);
    }

    /**
     * Проверка нарушений разделения обязанностей
     */
    private function checkSegregationViolations($user, string $operationType, Request $request): array
    {
        $violations = [];

        return match ($operationType) {
            'document_confirm' => $this->checkDocumentConfirmation($user, $request),
            'batch_block' => $this->checkBatchBlocking($user, $request),
            'prescription_dispense' => $this->checkPrescriptionDispensing($user, $request),
            'controlled_substance' => $this->checkControlledSubstance($user, $request),
            default => [],
        };
    }

    /**
     * Проверка для подтверждения документов
     * Создатель не может подтвердить свой документ
     */
    private function checkDocumentConfirmation($user, Request $request): array
    {
        $documentId = $request->route('document_id');
        
        if (!$documentId) {
            return [];
        }

        // Проверка, является ли пользователь создателем документа
        $document = \Illuminate\Support\Facades\DB::table('pharmaceutical_documents')
            ->where('id', $documentId)
            ->first();

        if ($document && $document->created_by === $user->id) {
            return [
                'rule' => 'document_creator_cannot_confirm',
                'message' => 'Document creator cannot confirm their own document',
            ];
        }

        return [];
    }

    /**
     * Проверка для блокировки партий
     * Требует отдельной роли
     */
    private function checkBatchBlocking($user, Request $request): array
    {
        if (!$user->hasRole('quality_manager') && !$user->hasRole('compliance_officer')) {
            return [
                'rule' => 'insufficient_role',
                'message' => 'Batch blocking requires quality_manager or compliance_officer role',
            ];
        }

        return [];
    }

    /**
     * Проверка для отпуска рецептурных препаратов
     * Отпуск и утверждение должны быть разными пользователями
     */
    private function checkPrescriptionDispensing($user, Request $request): array
    {
        // Для контролируемых веществ требуется двойной контроль
        $drugCategory = $request->input('drug_category');

        if (in_array($drugCategory, ['narcotic', 'psychotropic'])) {
            $approverId = $request->input('approver_id');

            if (!$approverId || $approverId === $user->id) {
                return [
                    'rule' => 'dual_control_required',
                    'message' => 'Controlled substances require dual control (different approver)',
                ];
            }
        }

        return [];
    }

    /**
     * Проверка для контролируемых веществ
     * Требует двухфакторной аутентификации и двойного контроля
     */
    private function checkControlledSubstance($user, Request $request): array
    {
        if (!$user->two_factor_enabled) {
            return [
                'rule' => '2fa_required',
                'message' => 'Controlled substance operations require 2FA',
            ];
        }

        $secondUserId = $request->input('second_user_id');

        if (!$secondUserId || $secondUserId === $user->id) {
            return [
                'rule' => 'dual_control_required',
                'message' => 'Controlled substances require dual control',
            ];
        }

        return [];
    }
}
