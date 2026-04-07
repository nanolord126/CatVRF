<?php

declare(strict_types=1);

namespace App\Domains\Staff\Presentation\Http\Controllers\Api\V1;


use Illuminate\Contracts\Routing\ResponseFactory;
use App\Domains\Staff\Application\UseCases\B2C\GetStaffPublicProfileUseCase;
use App\Domains\Staff\Presentation\Http\Resources\StaffPublicProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * StaffController — B2C API-контроллер для публичных профилей сотрудников.
 *
 * Не использует статические фасады. LoggerInterface привязан к audit-каналу
 * через StaffServiceProvider.
 */
final class StaffController
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly GetStaffPublicProfileUseCase $getPublicProfile,
        private readonly LoggerInterface              $auditLogger) {

    }

    /**
     * Возвращает публичный профиль сотрудника по UUID.
     *
     * GET /api/v1/staff/{staffId}/profile
     *
     * @param  Request $request HTTP-запрос (для логирования IP).
     * @param  string  $staffId UUID сотрудника из URL.
     * @return JsonResponse     200 с профилем, 404 если не найден, 422 для невалидного UUID, 500 при ошибке.
     */
    public function profile(Request $request, string $staffId): JsonResponse
    {
        $correlationId = Uuid::uuid4()->toString();

        try {
            $this->validateStaffId($staffId);

            $this->auditLogger->info('StaffController: profile requested.', [
                'staff_id'       => $staffId,
                'ip'             => $request->ip(),
                'correlation_id' => $correlationId,
            ]);

            $profile = $this->getPublicProfile->execute($staffId);

            return (new StaffPublicProfileResource($profile))
                ->$this->responseFactory
                ->setStatusCode(Response::HTTP_OK);

        } catch (\DomainException $e) {
            $this->auditLogger->warning('StaffController: staff member not found.', [
                'staff_id'       => $staffId,
                'reason'         => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'message'        => 'Сотрудник не найден.',
                'correlation_id' => $correlationId,
            ], Response::HTTP_NOT_FOUND);

        } catch (\InvalidArgumentException $e) {
            $this->auditLogger->warning('StaffController: invalid staff UUID.', [
                'staff_id'       => $staffId,
                'reason'         => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'message'        => 'Неверный формат идентификатора сотрудника.',
                'correlation_id' => $correlationId,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (Throwable $e) {
            $this->auditLogger->error('StaffController: unexpected error.', [
                'staff_id'       => $staffId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'message'        => 'Внутренняя ошибка сервера. Попробуйте позже.',
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Проверяет, что переданный staffId является валидным UUID v4.
     *
     * @throws \InvalidArgumentException
     */
    private function validateStaffId(string $staffId): void
    {
        try {
            Uuid::fromString($staffId);
        } catch (InvalidUuidStringException $e) {
            throw new \InvalidArgumentException(
                "staffId '{$staffId}' is not a valid UUID.",
                previous: $e,
            );
        }
    }
}
