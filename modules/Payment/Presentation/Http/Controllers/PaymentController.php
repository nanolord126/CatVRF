<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Payment\Application\Services\PaymentService;
use Modules\Payment\Application\Services\AMLService;
use Modules\Payment\Application\Services\FiscalizationService;
use Modules\Payment\Application\Services\PaymentRulesService;
use Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Entities\Payment;
use Psr\Log\LoggerInterface;

final readonly class PaymentController
{
    public function __construct(
        private PaymentService $paymentService,
        private AMLService $amlService,
        private FiscalizationService $fiscalizationService,
        private PaymentRulesService $paymentRulesService,
        private PaymentRepositoryInterface $paymentRepository,
        private LoggerInterface $logger,
    ) {}

    public function handleSuccess(Request $request, int $paymentId): JsonResponse
    {
        try {
            $request->validate([
                'gateway_transaction_id' => 'nullable|string|max:255',
                'gateway_response' => 'nullable|string',
            ]);

            $payment = $this->paymentRepository->findById($paymentId);
            if (!$payment) {
                return new JsonResponse(['error' => 'Payment not found'], 404);
            }

            $this->paymentService->handleSuccess(
                $payment,
                $request->input('gateway_transaction_id'),
                $request->input('gateway_response')
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Payment marked as successful',
                'payment_id' => $paymentId,
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Payment success handling failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function handleFailure(Request $request, int $paymentId): JsonResponse
    {
        try {
            $request->validate([
                'error_message' => 'nullable|string|max:1000',
            ]);

            $payment = $this->paymentRepository->findById($paymentId);
            if (!$payment) {
                return new JsonResponse(['error' => 'Payment not found'], 404);
            }

            $this->paymentService->handleFailure(
                $payment,
                $request->input('error_message')
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Payment marked as failed',
                'payment_id' => $paymentId,
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Payment failure handling failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function handlePartialPayment(Request $request, int $paymentId): JsonResponse
    {
        try {
            $payment = $this->paymentRepository->findById($paymentId);
            if (!$payment) {
                return new JsonResponse(['error' => 'Payment not found'], 404);
            }

            $this->paymentService->handlePartialPayment($payment);

            return new JsonResponse([
                'success' => true,
                'message' => 'Payment marked as partially paid',
                'payment_id' => $paymentId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Partial payment handling failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function handleRefund(Request $request, int $paymentId): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:1000',
            ]);

            $payment = $this->paymentRepository->findById($paymentId);
            if (!$payment) {
                return new JsonResponse(['error' => 'Payment not found'], 404);
            }

            $this->paymentService->handleRefund(
                $payment,
                $request->input('reason')
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Payment refunded successfully',
                'payment_id' => $paymentId,
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Payment refund handling failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function getPayment(int $paymentId): JsonResponse
    {
        try {
            $payment = $this->paymentRepository->findById($paymentId);
            if (!$payment) {
                return new JsonResponse(['error' => 'Payment not found'], 404);
            }

            return new JsonResponse([
                'success' => true,
                'payment' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'payable_type' => $payment->payableType,
                    'payable_id' => $payment->payableId,
                    'created_at' => $payment->createdAt->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Payment retrieval failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }
}
