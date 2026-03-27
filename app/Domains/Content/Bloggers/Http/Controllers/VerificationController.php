<?php

declare(strict_types=1);

namespace App\Domains\Content\Bloggers\Http\Controllers;

use App\Domains\Content\Bloggers\Models\BloggerProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class VerificationController extends Controller
{
    /**
     * GET /api/verification/status
     * Check verification status of current blogger
     */
    public function status(): JsonResponse
    {
        $profile = auth()->user()->bloggerProfile;

        return response()->json([
            'status' => $profile->verification_status,
            'verified_at' => $profile->verified_at,
            'rejection_reason' => $profile->rejection_reason,
            'documents_submitted' => count($profile->verification_documents ?? []),
            'can_resubmit' => $profile->verification_status === 'rejected',
        ]);
    }

    /**
     * POST /api/verification/documents
     * Submit verification documents
     */
    public function submitDocuments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'documents' => 'required|array|min:1',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $profile = auth()->user()->bloggerProfile;

        if ($profile->verification_status === 'verified') {
            return response()->json([
                'error' => 'Уже верифицирован',
            ], 422);
        }

        if ($profile->verification_status === 'pending') {
            return response()->json([
                'error' => 'Документы уже на рассмотрении',
            ], 422);
        }

        $documentPaths = [];
        foreach ($request->file('documents') as $document) {
            $path = $document->store('verification-documents', 'private');
            $documentPaths[] = $path;
        }

        DB::transaction(function () use ($profile, $documentPaths) {
            $profile->update([
                'verification_status' => 'pending',
                'verification_documents' => array_merge(
                    $profile->verification_documents ?? [],
                    $documentPaths
                ),
            ]);

            Log::channel('audit')->info('Blogger submitted verification documents', [
                'blogger_id' => $profile->id,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Str::uuid(),
                'document_count' => count($documentPaths),
            ]);
        });

        return response()->json([
            'message' => 'Документы отправлены на рассмотрение',
            'status' => 'pending',
        ]);
    }

    /**
     * GET /api/verification/requirements
     * Get verification requirements
     */
    public function requirements(): JsonResponse
    {
        return response()->json([
            'requirements' => [
                [
                    'title' => 'Основные документы',
                    'items' => [
                        'Копия паспорта (первая страница)',
                        'Копия ИНН',
                        'Договор о сотрудничестве (подписанный)',
                    ],
                ],
                [
                    'title' => 'Реквизиты для выплат',
                    'items' => [
                        'Номер расчётного счёта',
                        'БИК банка',
                        'Название банка',
                    ],
                ],
                [
                    'title' => 'Контактная информация',
                    'items' => [
                        'Email для связи',
                        'Номер телефона',
                        'Страна проживания',
                    ],
                ],
            ],
            'processing_time' => '1-3 рабочих дня',
            'contact_support' => 'support@bloggers.local',
        ]);
    }

    /**
     * POST /api/verification/appeal
     * Appeal rejection decision
     */
    public function appeal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|min:10|max:1000',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $profile = auth()->user()->bloggerProfile;

        if ($profile->verification_status !== 'rejected') {
            return response()->json([
                'error' => 'Можно обжаловать только отклоненную верификацию',
            ], 422);
        }

        DB::transaction(function () use ($profile, $validated) {
            $documentPaths = [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $document) {
                    $path = $document->store('verification-appeals', 'private');
                    $documentPaths[] = $path;
                }
            }

            $profile->update([
                'verification_status' => 'pending',
                'verification_documents' => array_merge(
                    $profile->verification_documents ?? [],
                    $documentPaths
                ),
            ]);

            Log::channel('audit')->info('Blogger appealed rejection', [
                'blogger_id' => $profile->id,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Str::uuid(),
                'message' => $validated['message'],
            ]);
        });

        return response()->json([
            'message' => 'Обжалование отправлено на рассмотрение',
            'status' => 'pending',
        ]);
    }
}
