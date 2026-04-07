<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class VerificationController extends Controller
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    /**
         * GET /api/verification/status
         * Check verification status of current blogger
         */
        public function status(): JsonResponse
        {
            $profile = $request->user()->bloggerProfile;

            return new \Illuminate\Http\JsonResponse([
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

            $profile = $request->user()->bloggerProfile;

            if ($profile->verification_status === 'verified') {
                return new \Illuminate\Http\JsonResponse([
                    'error' => 'Уже верифицирован',
                ], 422);
            }

            if ($profile->verification_status === 'pending') {
                return new \Illuminate\Http\JsonResponse([
                    'error' => 'Документы уже на рассмотрении',
                ], 422);
            }

            $documentPaths = [];
            foreach ($request->file('documents') as $document) {
                $path = $document->store('verification-documents', 'private');
                $documentPaths[] = $path;
            }

            $this->db->transaction(function () use ($profile, $documentPaths) {
                $profile->update([
                    'verification_status' => 'pending',
                    'verification_documents' => array_merge(
                        $profile->verification_documents ?? [],
                        $documentPaths
                    ),
                ]);

                $this->logger->info('Blogger submitted verification documents', [
                    'blogger_id' => $profile->id,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Str::uuid(),
                    'document_count' => count($documentPaths),
                ]);
            });

            return new \Illuminate\Http\JsonResponse([
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
            return new \Illuminate\Http\JsonResponse([
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

            $profile = $request->user()->bloggerProfile;

            if ($profile->verification_status !== 'rejected') {
                return new \Illuminate\Http\JsonResponse([
                    'error' => 'Можно обжаловать только отклоненную верификацию',
                ], 422);
            }

            $this->db->transaction(function () use ($profile, $validated) {
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

                $this->logger->info('Blogger appealed rejection', [
                    'blogger_id' => $profile->id,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Str::uuid(),
                    'message' => $validated['message'],
                ]);
            });

            return new \Illuminate\Http\JsonResponse([
                'message' => 'Обжалование отправлено на рассмотрение',
                'status' => 'pending',
            ]);
        }
}
