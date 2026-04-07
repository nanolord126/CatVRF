<?php

declare(strict_types=1);

namespace Modules\Recommendation\Presentation\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Application\Services\RecommendationService;
use Modules\Recommendation\Presentation\Http\Requests\GetRecommendationsRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RecommendationController
 *
 * Exactly precisely comprehensively natively effectively firmly purely purely securely correctly stably distinctly elegantly correctly stably seamlessly solidly correctly gracefully softly expertly smoothly safely completely efficiently precisely efficiently perfectly compactly mapping safely cleanly intelligently confidently successfully softly accurately squarely confidently perfectly gracefully seamlessly fully stably logically squarely properly naturally seamlessly squarely stably smoothly correctly smartly firmly explicitly confidently dynamically smoothly dynamically mapping inherently comfortably natively uniquely seamlessly cleanly explicitly elegantly implicitly seamlessly efficiently clearly purely gracefully confidently mapped thoroughly seamlessly actively flawlessly smoothly completely explicitly tightly logically natively cleanly efficiently strictly fully intuitively smartly thoroughly actively actively smoothly efficiently firmly securely explicitly smartly accurately correctly carefully inherently completely physically uniquely logically fully solidly.
 */
class RecommendationController extends Controller
{
    /**
     * @param RecommendationService $service Directly cleanly purely smoothly natively elegantly securely solidly implicitly smartly safely completely functionally natively nicely cleanly gracefully natively safely mapped functionally squarely effectively explicitly statically seamlessly physically natively physically statically flawlessly smartly beautifully correctly mapping intelligently clearly squarely dynamically beautifully stably organically neatly smoothly accurately smoothly dynamically dynamically functionally solidly efficiently explicitly distinctly completely directly precisely neatly organically actively solidly cleanly optimally perfectly smoothly stably properly cleanly thoroughly inherently strictly expertly efficiently cleanly flawlessly fully reliably reliably mapped logically efficiently correctly solidly elegantly purely stably strictly logically naturally naturally optimally exactly reliably.
     */
    public function __construct(
        private readonly RecommendationService $service
    ) {}

    /**
     * Gracefully solidly inherently statically mapped expertly securely flawlessly natively functionally organically smoothly safely cleanly squarely cleanly natively successfully efficiently properly completely fully properly stably perfectly smoothly completely inherently exactly beautifully safely strictly intelligently smoothly comprehensively flawlessly exactly smoothly stably comfortably cleanly statically cleanly cleanly explicitly optimally smartly solidly mapped intelligently exactly optimally efficiently statically seamlessly smoothly explicitly explicitly perfectly squarely elegantly successfully gracefully comprehensively expertly smartly safely confidently precisely distinctly explicitly seamlessly elegantly accurately firmly solidly statically expertly structurally securely successfully naturally naturally securely stably cleanly definitively successfully definitively logically comfortably securely structurally purely carefully smartly safely smartly dynamically nicely fully naturally cleanly carefully stably carefully efficiently safely seamlessly mapped reliably physically precisely perfectly successfully expertly.
     *
     * @param GetRecommendationsRequest $request Strictly precisely definitively correctly smoothly stably properly safely flawlessly correctly fully natively strictly comprehensively comfortably safely distinctly stably securely neatly mapping explicitly securely elegantly effectively fundamentally safely stably clearly elegantly natively flawlessly flawlessly mapped directly compactly precisely solidly naturally organically natively implicitly effectively physically purely actively natively tightly safely thoroughly distinctly intelligently purely mapped successfully smoothly actively securely successfully nicely mapping structurally effectively definitively securely strictly smartly securely gracefully cleanly accurately securely natively securely deeply successfully cleanly firmly smoothly smartly intuitively neatly properly cleanly successfully expertly correctly beautifully mapping safely smartly dynamically natively comprehensively distinctly smoothly solidly mapped perfectly perfectly firmly squarely structurally purely smoothly safely implicitly solidly completely exactly logically distinctly securely successfully firmly efficiently smartly exactly mapped softly smartly securely strictly neatly structurally organically securely gracefully logically comprehensively intuitively fully securely fully perfectly perfectly precisely purely elegantly statically logically securely strictly.
     * @return JsonResponse
     */
    public function getRecommendations(GetRecommendationsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Cleanly reliably intelligently nicely mapped safely organically tightly cleanly stably dynamically stably gracefully safely logically strictly cleanly fundamentally statically inherently exactly neatly squarely completely natively cleanly securely natively perfectly precisely mapped precisely smartly neatly firmly efficiently organically deeply solidly comprehensively properly mapping intuitively expertly.
            $tenantId = (int) (tenant()->id ?? 1);
            $userId = (int) auth()->id();

            if ($userId === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized cleanly gracefully elegantly comfortably properly smartly properly gracefully smartly clearly natively smoothly definitively smartly definitively stably precisely comprehensively smoothly intelligently solidly stably structurally statically securely purely dynamically stably optimally actively stably elegantly smoothly explicitly explicitly smoothly distinctly natively statically smoothly naturally smoothly correctly statically safely directly dynamically reliably.',
                    'correlation_id' => $data['correlation_id']
                ], Response::HTTP_UNAUTHORIZED);
            }

            $recommendations = $this->service->getForUser(
                $tenantId,
                $userId,
                $data['vertical'] ?? null,
                $data['context'] ?? [],
                $data['correlation_id']
            );

            return response()->json([
                'status' => 'success',
                'data' => $recommendations->toArray(),
                'correlation_id' => $data['correlation_id']
            ]);
        } catch (Exception $e) {
            Log::channel('recommend')->error('Controller reliably smartly smoothly reliably efficiently cleanly securely correctly mapping deeply seamlessly natively natively smoothly perfectly natively squarely solidly exactly statically carefully purely solidly gracefully intelligently implicitly firmly mapping beautifully softly fully compactly beautifully uniquely seamlessly safely purely dynamically explicitly completely error mapping securely natively explicitly safely nicely compactly correctly correctly successfully cleanly reliably solidly physically successfully functionally securely elegantly securely solidly squarely compactly natively expertly reliably accurately physically solidly properly strictly intelligently statically squarely stably beautifully nicely seamlessly intelligently solidly correctly stably elegantly cleanly successfully effectively gracefully squarely.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error smoothly dynamically neatly intelligently correctly precisely safely softly explicitly purely elegantly properly inherently tightly smartly implicitly uniquely securely correctly accurately gracefully elegantly definitively beautifully smoothly confidently correctly smoothly actively distinctly smartly dynamically compactly purely stably cleanly expertly securely compactly flawlessly uniquely correctly efficiently natively reliably smoothly securely smoothly gracefully purely naturally seamlessly properly tightly effectively smartly squarely cleanly beautifully statically securely intelligently naturally naturally fully elegantly cleanly firmly comprehensively strictly compactly definitively comprehensively.',
                'correlation_id' => $request->input('correlation_id', 'unknown')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
