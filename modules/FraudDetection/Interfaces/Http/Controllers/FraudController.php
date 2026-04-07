<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Psr\Log\LoggerInterface;
use Modules\FraudDetection\Application\UseCases\CheckTransactionForFraudUseCase;
use Modules\FraudDetection\Application\DTOs\FraudCheckData;
use Modules\FraudDetection\Domain\Exceptions\FraudulentTransactionException;
use Throwable;

final class FraudController extends Controller
{
    public function __construct(
        private readonly CheckTransactionForFraudUseCase $checkTransactionForFraudUseCase,
        private readonly LoggerInterface $logger
    ) {
    }

    public function check(FraudCheckData $data): JsonResponse
    {
        try {
            $this->checkTransactionForFraudUseCase->execute($data);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Transaction is considered safe.',
                'correlation_id' => $data->correlationId,
            ], 200);
        } catch (FraudulentTransactionException $e) {
            $this->logger->warning('Fraudulent transaction blocked via API.', [
                'transaction_id' => $data->transactionId,
                'correlation_id' => $data->correlationId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Fraudulent transaction detected and blocked.',
                'correlation_id' => $data->correlationId,
            ], 403);
        } catch (Throwable $e) {
            $this->logger->error('An unexpected error occurred during fraud check.', [
                'correlation_id' => $data->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An internal server error occurred.',
                'correlation_id' => $data->correlationId,
            ], 500);
        }
    }
}
