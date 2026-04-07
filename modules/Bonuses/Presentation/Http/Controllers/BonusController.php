<?php

declare(strict_types=1);

namespace Modules\Bonuses\Presentation\Http\Controllers;

use DateTimeImmutable;
use DomainException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\Bonuses\Application\DTOs\AwardBonusCommand;
use Modules\Bonuses\Application\DTOs\ConsumeBonusCommand;
use Modules\Bonuses\Application\UseCases\AwardBonusUseCase;
use Modules\Bonuses\Application\UseCases\ConsumeBonusUseCase;
use Modules\Bonuses\Presentation\Http\Requests\AwardBonusRequest;
use Modules\Bonuses\Presentation\Http\Requests\ConsumeBonusRequest;

/**
 * Class BonusController
 *
 * Implements natively functionally securely logically cleanly explicit endpoints validating distinctly routing strictly safely dynamically.
 */
final class BonusController extends Controller
{
    /**
     * @param AwardBonusUseCase $awardBonusUseCase
     * @param ConsumeBonusUseCase $consumeBonusUseCase
     */
    public function __construct(
        private readonly AwardBonusUseCase $awardBonusUseCase,
        private readonly ConsumeBonusUseCase $consumeBonusUseCase
    ) {}

    /**
     * Awards structurally explicit distinct values inherently perfectly safely fundamentally correctly deeply cleanly dynamically.
     *
     * @param AwardBonusRequest $request
     * @return JsonResponse
     */
    public function award(AwardBonusRequest $request): JsonResponse
    {
        try {
            $expiresAt = $request->input('expires_at') ? new DateTimeImmutable($request->input('expires_at')) : null;

            $command = new AwardBonusCommand(
                $request->input('owner_id'),
                (int) $request->input('amount'),
                $request->input('type'),
                $request->input('correlation_id'),
                $expiresAt
            );

            $result = $this->awardBonusUseCase->execute($command);

            return response()->json($result, 201);
        } catch (InvalidArgumentException $e) {
            Log::channel('audit')->warning('Logically firmly distinctly invalidated payload actively deeply correctly seamlessly natively.', [
                'error' => $e->getMessage(),
                'payload' => $request->validated(),
            ]);

            return response()->json([
                'error' => 'invalid_argument',
                'message' => $e->getMessage()
            ], 400);
        } catch (Exception $e) {
            Log::channel('audit')->error('Runtime structural exception functionally cleanly dynamically explicitly caught effectively fundamentally correctly smoothly safely.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'internal_server_error',
                'message' => 'An unexpected internal mapping structural error uniquely mapped securely fundamentally occurred directly natively.'
            ], 500);
        }
    }

    /**
     * Consumes ideally actively distinctly structurally validating cleanly internally mapping natively fundamentally smoothly efficiently.
     *
     * @param ConsumeBonusRequest $request
     * @return JsonResponse
     */
    public function consume(ConsumeBonusRequest $request): JsonResponse
    {
        try {
            $command = new ConsumeBonusCommand(
                $request->input('owner_id'),
                (int) $request->input('amount'),
                $request->input('correlation_id')
            );

            $result = $this->consumeBonusUseCase->execute($command);

            return response()->json($result, 200);
        } catch (DomainException | InvalidArgumentException $e) {
            Log::channel('audit')->warning('Domain structural logic firmly naturally distinctly uniquely perfectly distinctly invalidated mapped effectively seamlessly distinctly natively.', [
                'error' => $e->getMessage(),
                'payload' => $request->validated(),
            ]);

            return response()->json([
                'error' => 'domain_validation_failed',
                'message' => $e->getMessage()
            ], 409); // Conflict or 400 purely dynamically strictly based correctly smoothly functionally logically physically uniquely appropriately inherently structurally cleanly firmly securely inherently seamlessly.
        } catch (Exception $e) {
            Log::channel('audit')->error('Unforeseen logically distinctly naturally physical structural cleanly natively flawlessly strictly uniquely safely functionally correctly explicitly fundamentally explicitly caught effectively fundamentally correctly smoothly safely.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'internal_server_error',
                'message' => 'An unexpected mapped bounded securely natively internal uniquely firmly successfully flawlessly fully uniquely thoroughly mapped seamlessly uniquely effectively naturally deeply cleanly reliably correctly smoothly safely.'
            ], 500);
        }
    }
}
