<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Presentation\B2C\API\Controllers;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Application\B2C\DTOs\SalonSearchDTO;
use App\Domains\Beauty\Application\B2C\UseCases\GetSalonDetailsUseCase;
use App\Domains\Beauty\Application\B2C\UseCases\SearchSalonsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class SalonDetailsController extends Controller
{
    public function __construct(
        private GetSalonDetailsUseCase $detailsUseCase,
        private SearchSalonsUseCase $searchUseCase,
        private LoggerInterface $logger,
    ) {}

    /**
     * Поиск и список салонов (B2C публичный).
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $dto = new SalonSearchDTO(
                city: $request->string('city')->toString() ?: null,
                specialization: $request->string('specialization')->toString() ?: null,
                latitude: $request->float('lat') ?: null,
                longitude: $request->float('lon') ?: null,
                radiusKm: $request->float('radius', 5.0),
                page: $request->integer('page', 1),
                perPage: min($request->integer('per_page', 20), 100),
            );

            $salons = ($this->searchUseCase)($dto);

            $this->logger->info('Beauty B2C: поиск салонов', [
                'city'           => $dto->city,
                'correlation_id' => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => true,
                'correlation_id' => $correlationId,
                'data'           => $salons,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Beauty B2C: ошибка поиска салонов', [
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => false,
                'message'        => 'Не удалось выполнить поиск салонов.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Детальная карточка салона с мастерами, услугами, расписанием.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $salon = ($this->detailsUseCase)($uuid);

            $this->logger->info('Beauty B2C: просмотр салона', [
                'uuid'           => $uuid,
                'correlation_id' => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => true,
                'correlation_id' => $correlationId,
                'data'           => $salon,
            ]);
        } catch (\DomainException $e) {
            return new \Illuminate\Http\JsonResponse([
                'success'        => false,
                'message'        => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 404);
        } catch (\Throwable $e) {
            $this->logger->error('Beauty B2C: ошибка получения салона', [
                'uuid'           => $uuid,
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new \Illuminate\Http\JsonResponse([
                'success'        => false,
                'message'        => 'Не удалось загрузить информацию о салоне.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
