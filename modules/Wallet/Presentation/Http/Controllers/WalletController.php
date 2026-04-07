<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Wallet\Application\UseCases\Credit\CreditWalletCommand;
use Modules\Wallet\Application\UseCases\Credit\CreditWalletUseCase;
use Modules\Wallet\Application\UseCases\Debit\DebitWalletCommand;
use Modules\Wallet\Application\UseCases\Debit\DebitWalletUseCase;
use Modules\Wallet\Application\UseCases\Transfer\TransferWalletCommand;
use Modules\Wallet\Application\UseCases\Transfer\TransferWalletUseCase;
use Modules\Wallet\Presentation\Http\Requests\CreditWalletRequest;
use Modules\Wallet\Presentation\Http\Requests\DebitWalletRequest;
use Modules\Wallet\Presentation\Http\Requests\TransferWalletRequest;
use Modules\Wallet\Domain\Exceptions\WalletNotFoundException;
use Throwable;

/**
 * Class WalletController
 *
 * Implements the explicit RESTful Presentation boundary for all wallet mutation requests.
 * Inherently isolates external HTTP specifics (Requests, Responses, JSON mapping)
 * away from the underlying pure UseCases. Ensures proper error catching and 
 * normalization into clean JSON APIs.
 */
final class WalletController extends Controller
{
    /**
     * Injecting exact primary orchestrators.
     * 
     * @param CreditWalletUseCase $creditUseCase Manages wallet fund additions.
     * @param DebitWalletUseCase $debitUseCase Manages wallet fund removals safely.
     * @param TransferWalletUseCase $transferUseCase Orchestrates inter-wallet flow cleanly.
     */
    public function __construct(
        private readonly CreditWalletUseCase $creditUseCase,
        private readonly DebitWalletUseCase $debitUseCase,
        private readonly TransferWalletUseCase $transferUseCase
    ) {
    }

    /**
     * Explicit API endpoint initiating the external command mapping into the primary UseCase sequence.
     * Adds funds robustly to a target wallet verifying rules and enforcing boundaries.
     *
     * @param CreditWalletRequest $request Validated structural properties reflecting specific domain semantics.
     * @return JsonResponse Explicit JSON response.
     */
    public function credit(CreditWalletRequest $request): JsonResponse
    {
        try {
            $command = new CreditWalletCommand(
                walletId: $request->validated('wallet_id'),
                amount: (int) $request->validated('amount'),
                reason: $request->validated('reason'),
                tenantId: $request->validated('tenant_id'),
                correlationId: $request->validated('correlation_id')
            );

            $result = $this->creditUseCase->execute($command);

            return response()->json([
                'success' => true,
                'data'    => $result->toArray(),
            ], 200);

        } catch (WalletNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'error'   => 'WALLET_NOT_FOUND',
                'message' => $exception->getMessage(),
            ], 404);
        } catch (Throwable $exception) {
            // General infrastructural mapping avoiding tight bindings over domain leaks.
            // Sentry or central handlers logic intercept this typically.
            return response()->json([
                'success' => false,
                'error'   => 'INTERNAL_SERVER_ERROR',
                'message' => 'An unexpected constraint failure occurred during crediting process. ' . $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiates a deterministic fund deduction from the authorized wallet source, 
     * inherently enforcing bounds check natively.
     *
     * @param DebitWalletRequest $request Enforced schema ensuring data shapes properly format.
     * @return JsonResponse Restful HTTP envelope.
     */
    public function debit(DebitWalletRequest $request): JsonResponse
    {
        try {
            $command = new DebitWalletCommand(
                walletId: $request->validated('wallet_id'),
                amount: (int) $request->validated('amount'),
                reason: $request->validated('reason'),
                tenantId: $request->validated('tenant_id'),
                correlationId: $request->validated('correlation_id')
            );

            $result = $this->debitUseCase->execute($command);

            return response()->json([
                'success' => true,
                'data'    => $result->toArray(),
            ], 200);

        } catch (WalletNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'error'   => 'WALLET_NOT_FOUND',
                'message' => $exception->getMessage(),
            ], 404);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'error'   => 'INTERNAL_SERVER_ERROR',
                'message' => 'Exception triggered during absolute deduction tracking. ' . $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Executes the combined inter-wallet data sequence locking two aggregates inherently
     * avoiding negative overlap conditions safely.
     *
     * @param TransferWalletRequest $request The strictly validated representation of transfer constraints.
     * @return JsonResponse Clean REST format mapping.
     */
    public function transfer(TransferWalletRequest $request): JsonResponse
    {
        try {
            $command = new TransferWalletCommand(
                sourceWalletId: $request->validated('source_wallet_id'),
                targetWalletId: $request->validated('target_wallet_id'),
                amount: (int) $request->validated('amount'),
                tenantId: $request->validated('tenant_id'),
                correlationId: $request->validated('correlation_id'),
                reason: $request->validated('reason')
            );

            $result = $this->transferUseCase->execute($command);

            return response()->json([
                'success' => true,
                'data'    => $result->toArray(),
            ], 200);

        } catch (WalletNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'error'   => 'WALLET_LOCATING_ERROR',
                'message' => $exception->getMessage(),
            ], 404);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'error'   => 'TRANSFER_TRANSACTION_FAILED',
                'message' => 'Integrity violation during inter-wallet movement. ' . $exception->getMessage(),
            ], 500);
        }
    }
}
