<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Education;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EducationController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly EducationManagementService $educationService,
        ) {}
        /**
         * Создать Enrollment (B2B/B2C).
         * POST /api/v1/education/enroll
         */
        public function enroll(EnrollmentRequest $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id', (string) Str::uuid());
            Log::channel('audit')->info('Api Education: Enrollment request received', [
                'user_id' => $request->user()->id,
                'course_uuid' => $request->get('course_uuid'),
                'correlation_id' => $correlationId,
            ]);
            try {
                $course = Course::where('uuid', $request->get('course_uuid'))->firstOrFail();
                $user = $request->user();
                // Если передан UUID контракта - это B2B
                if ($contractUuid = $request->get('corporate_contract_uuid')) {
                    $contract = CorporateContract::where('uuid', $contractUuid)->firstOrFail();
                    $enrollment = $this->educationService->enrollUserUnderContract($user, $contract, $course, $correlationId);
                    $mode = 'B2B (Corporate Slot)';
                } else {
                    // Прямое зачисление B2C (через покупку)
                    $enrollment = $this->educationService->enrollUserDirectly($user, $course, $correlationId);
                    $mode = 'B2C (Direct Purchase)';
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully enrolled in ' . $course->title,
                    'mode' => $mode,
                    'enrollment_id' => $enrollment->uuid,
                    'ai_path' => $enrollment->ai_path,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\BalanceInsufficientException $e) {
                return response()->json([
                    'error' => 'Wallet balance insufficient for B2C enrollment.',
                    'correlation_id' => $correlationId,
                ], 402);
            } catch (\SlotLimitExceededException $e) {
                return response()->json([
                    'error' => 'Corporate contract slots exceeded.',
                    'correlation_id' => $correlationId,
                ], 403);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Api Education: Enrollment failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'error' => 'An internal server error occurred during education process.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Получить список доступных курсов (B2C Каталог).
         * GET /api/v1/education/courses
         */
        public function index(): JsonResponse
        {
            $courses = Course::where('status', 'published')
                ->select(['uuid', 'title', 'price_kopecks', 'level', 'category'])
                ->get();
            return response()->json([
                'success' => true,
                'courses' => $courses,
            ]);
        }
        /**
         * Получить активные контракты пользователя (B2B).
         * GET /api/v1/education/contracts
         */
        public function contracts(\Illuminate\Http\Request $request): JsonResponse
        {
            $userTenantId = $request->user()->tenant_id;
            $contracts = CorporateContract::where('client_tenant_id', $userTenantId)
                ->where('status', 'active')
                ->get();
            return response()->json([
                'success' => true,
                'contracts' => $contracts,
            ]);
        }
}
