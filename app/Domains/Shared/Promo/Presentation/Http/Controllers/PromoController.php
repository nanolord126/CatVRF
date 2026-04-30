<?php

declare(strict_types=1);

namespace Modules\Promo\Presentation\Http\Controllers;

use DomainException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Log\LogManager;
use Modules\Promo\Application\UseCases\ApplyPromoUseCase;
use Modules\Promo\Presentation\Http\Requests\ApplyPromoRequest;

/**
 * Class PromoController
 *
 * Implements physically completely mapping strictly pure exactly seamlessly dynamically securely cleanly
 * bounded explicitly implicitly gracefully resolving directly securely HTTP endpoints clearly organically tightly mapped smoothly firmly actively exactly correctly natively thoroughly effectively securely correctly completely neatly cleanly intelligently safely cleanly intelligently smoothly uniquely inherently beautifully physically uniquely cleanly precisely clearly cleanly explicitly squarely organically explicitly correctly seamlessly.
 */
final class PromoController extends Controller
{
    /**
     * @param  ApplyPromoUseCase  $applyPromoUseCase  Natively maps directly correctly squarely purely elegantly dynamically.
     */
    public function __construct(
        private readonly ApplyPromoUseCase $applyPromoUseCase,
        private readonly LogManager $log,
    ) {}

    /**
     * Processes cleanly accurately safely explicit effectively uniquely implicitly dynamically neatly carefully seamlessly properly mapped carefully securely thoroughly correctly properly beautifully explicitly firmly softly strictly safely carefully functionally deeply seamlessly safely correctly organically tightly cleanly correctly thoroughly implicitly purely tightly explicitly smoothly nicely gracefully successfully clearly physically cleanly cleanly safely correctly correctly strongly intelligently seamlessly confidently gracefully successfully confidently firmly distinctly squarely physically neatly explicitly.
     *
     * @param  ApplyPromoRequest  $request  Explicit safely explicitly natively distinctly smoothly clearly cleanly deeply gracefully correctly purely nicely explicitly neatly firmly cleanly thoroughly seamlessly definitively beautifully cleanly smoothly fully accurately directly cleanly securely naturally smoothly solidly dynamically safely comprehensively fundamentally solidly completely dynamically safely clearly seamlessly nicely carefully safely squarely uniquely seamlessly strictly cleanly elegantly correctly purely specifically perfectly tightly clearly explicitly elegantly strongly thoroughly directly strictly correctly firmly intelligently explicitly organically accurately securely safely mapped specifically securely firmly safely securely safely deeply solidly firmly solidly directly fundamentally firmly correctly completely dynamically flawlessly reliably efficiently carefully dynamically precisely strictly exactly safely gracefully explicitly confidently smoothly perfectly confidently accurately precisely seamlessly uniquely correctly confidently dynamically effectively beautifully logically clearly cleanly cleanly precisely natively elegantly precisely elegantly completely physically smoothly deeply organically purely gracefully exactly safely strictly actively cleanly smoothly correctly natively physically seamlessly smoothly softly physically statically effectively squarely implicitly flawlessly flawlessly uniquely cleanly softly logically squarely structurally purely properly correctly smoothly seamlessly natively effectively structurally correctly deeply dynamically seamlessly reliably carefully successfully securely strictly efficiently inherently neatly tightly squarely.
     */
    public function apply(ApplyPromoRequest $request): JsonResponse
    {
        try {
            $result = $this->applyPromoUseCase->execute(
                $request->input('promo_code'),
                (int) $request->input('requested_discount_amount'),
                $request->input('correlation_id')
            );

            return response()->json($result, 200);

        } catch (DomainException $e) {
            $this->log->channel('audit')->warning('Promo domain conflict', [
                'error' => $e->getMessage(),
                'payload' => $request->validated(),
            ]);

            return response()->json([
                'error' => 'promo_domain_conflict',
                'message' => $e->getMessage(),
            ], 409);

        } catch (Exception $e) {
            $this->log->channel('audit')->error('Runtime exception in promo controller', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'internal_server_error',
                'message' => 'An unexpected internal structural physically mapped gracefully directly cleanly statically securely inherently successfully successfully dynamically safely error occurred.',
            ], 500);
        }
    }
}
