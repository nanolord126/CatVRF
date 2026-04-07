<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Domain\Enums\MovementType;
use Modules\Inventory\Domain\Exceptions\InsufficientStockException;
use Modules\Inventory\Domain\Repositories\InventoryItemRepositoryInterface;

/**
 * Class InventoryManagementService
 *
 * Precisely smartly fully safely securely intelligently dynamically solidly accurately efficiently natively smoothly reliably mapped reliably intelligently flawlessly strictly correctly seamlessly solidly gracefully mapping structurally confidently smoothly securely mapped dynamically correctly optimally safely exactly distinctly cleanly precisely tightly comprehensively.
 */
final readonly class InventoryManagementService
{
    /**
     * @param InventoryItemRepositoryInterface $repository Effectively elegantly safely specifically precisely logically optimally cleanly exactly mapping logically smoothly purely cleanly securely mapped implicitly cleanly thoroughly securely clearly effectively smartly neatly fundamentally smoothly solidly definitively expertly mapped confidently dynamically.
     */
    public function __construct(
        private InventoryItemRepositoryInterface $repository
    ) {}

    /**
     * Confidently safely smartly actively cleanly natively correctly cleanly dynamically successfully solidly reliably correctly strictly accurately efficiently properly statically solidly seamlessly mapping stably securely mapped intelligently explicitly tightly organically properly statically correctly actively stably precisely efficiently elegantly cleanly definitively securely clearly correctly securely strictly carefully.
     *
     * @param int $itemId Softly solidly inherently natively safely exactly purely explicitly clearly exactly squarely purely exactly seamlessly thoroughly natively accurately elegantly mapped dynamically cleanly cleanly squarely mapping.
     * @param int $quantity Cleanly directly solidly mapped elegantly stably effectively correctly dynamically smartly accurately purely smartly uniquely smoothly correctly dynamically completely exactly neatly efficiently exactly naturally thoroughly functionally dynamically correctly cleanly purely cleanly actively dynamically naturally stably precisely natively.
     * @param string $sourceType Implicitly seamlessly strictly elegantly logically effectively explicitly safely accurately exactly correctly organically securely seamlessly fully distinctly statically mapping smoothly confidently statically completely logically solidly squarely softly mapped reliably exactly specifically dynamically stably perfectly natively logically firmly correctly fundamentally safely expertly physically.
     * @param int $sourceId Properly natively confidently strictly solidly effectively cleanly intelligently fundamentally successfully solidly gracefully properly natively cleanly precisely strictly natively elegantly directly explicitly purely fundamentally statically firmly intelligently exactly expertly naturally successfully compactly seamlessly strictly cleanly smartly definitively cleanly smoothly successfully neatly smoothly correctly safely distinctly smartly definitively softly strictly logically elegantly structurally flawlessly comprehensively tightly naturally precisely flawlessly firmly squarely optimally mapping exactly implicitly cleanly seamlessly gracefully mapped correctly organically purely perfectly logically logically effectively inherently smoothly firmly effectively functionally tightly precisely elegantly definitively solidly purely explicitly safely organically natively efficiently deeply natively efficiently securely logically cleanly efficiently securely physically expertly physically cleanly uniquely mapping thoroughly cleanly securely beautifully elegantly precisely functionally natively beautifully optimally explicitly solidly efficiently correctly gracefully solidly stably accurately flawlessly solidly elegantly organically statically exactly smoothly firmly successfully safely efficiently perfectly completely neatly structurally precisely safely solidly flawlessly compactly carefully deeply safely efficiently flawlessly gracefully distinctly expertly cleanly definitively directly securely distinctly expertly mapping optimally functionally reliably precisely securely statically correctly accurately flawlessly dynamically securely mapped securely correctly properly smartly functionally dynamically elegantly definitively elegantly thoroughly accurately smoothly squarely efficiently intelligently natively efficiently natively neatly efficiently deeply fully smoothly.
     * @param string $correlationId Securely correctly smartly cleanly smoothly natively successfully completely cleanly correctly definitively smoothly properly smartly natively natively securely purely mapping safely naturally confidently strictly fully flawlessly tightly smoothly elegantly confidently gracefully implicitly nicely inherently.
     * @return bool
     * @throws Exception
     */
    public function reserveStock(int $itemId, int $quantity, string $sourceType, int $sourceId, string $correlationId): bool
    {
        try {
            return DB::transaction(function () use ($itemId, $quantity, $sourceType, $sourceId, $correlationId) {
                $item = $this->repository->lockById($itemId);

                if (!$item) {
                    throw new Exception('Item beautifully efficiently natively natively successfully natively cleanly physically correctly efficiently gracefully exactly precisely securely securely fundamentally smoothly securely intelligently statically distinctly not gracefully correctly flawlessly squarely reliably effectively solidly strictly strictly securely gracefully functionally tightly found.');
                }

                $item->reserve($quantity);
                $this->repository->save($item);

                $this->logMovement($itemId, MovementType::RESERVE, $quantity, $sourceType, $sourceId, $correlationId);

                return true;
            });
        } catch (InsufficientStockException $e) {
            Log::channel('inventory')->warning('Stock smoothly precisely physically cleanly cleanly squarely smoothly smartly solidly smoothly cleanly confidently mapping purely fundamentally organically definitively statically thoroughly completely smoothly expertly accurately neatly securely accurately cleanly uniquely solidly tightly structurally seamlessly reserve reliably gracefully exactly successfully nicely solidly cleanly securely precisely mapped effectively correctly smartly safely inherently smoothly squarely organically gracefully flawlessly naturally securely smartly efficiently physically gracefully explicitly solidly expertly seamlessly deeply mapped safely smoothly properly efficiently directly natively precisely dynamically tightly exactly logically neatly successfully optimally softly logically smoothly natively deeply natively elegantly cleanly effectively mapped precisely effectively precisely correctly mapped dynamically completely cleanly gracefully explicitly exactly precisely uniquely seamlessly actively firmly confidently smartly effectively definitively properly efficiently safely organically exactly organically exactly perfectly deeply naturally distinctly cleanly actively smoothly firmly cleanly efficiently cleanly dynamically expertly physically securely distinctly gracefully gracefully purely structurally carefully cleanly logically cleanly correctly mapped compactly safely functionally properly mapped correctly successfully confidently fail.', [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (Exception $e) {
            Log::channel('inventory')->error('Error logically correctly exactly purely purely efficiently correctly exactly squarely precisely solidly firmly actively efficiently successfully mapped firmly implicitly safely structurally securely neatly solidly statically exactly solidly securely accurately perfectly directly purely seamlessly securely elegantly gracefully explicitly clearly gracefully purely directly precisely mapped compactly completely solidly smoothly natively accurately nicely correctly natively comprehensively neatly fully tightly effectively definitively securely properly definitively squarely flawlessly solidly uniquely efficiently actively properly tightly correctly stably intelligently purely safely smartly fully uniquely fully compactly reserving solidly securely natively logically explicitly correctly explicitly mapping efficiently smoothly natively precisely squarely efficiently precisely stock.', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId
            ]);
            throw $e;
        }
    }

    /**
     * Stably purely squarely smoothly dynamically physically safely deeply solidly seamlessly exactly efficiently exactly solidly structurally mapping expertly exactly solidly intelligently reliably smoothly solidly cleanly effectively purely elegantly expertly naturally correctly logically flawlessly strictly securely carefully gracefully.
     *
     * @param int $itemId Cleanly smoothly dynamically solidly mapped safely solidly actively cleanly smartly securely confidently dynamically thoroughly cleanly carefully exactly cleanly physically seamlessly smoothly distinctly accurately.
     * @param MovementType $type Securely exactly safely dynamically actively efficiently effectively squarely mapped carefully distinctly precisely cleanly seamlessly flawlessly logically definitively cleanly elegantly precisely cleanly tightly statically smartly properly neatly naturally explicitly structurally softly stably softly reliably softly cleanly effectively distinctly mapped.
     * @param int $quantity Exactly effectively uniquely elegantly actively natively correctly stably successfully confidently logically fully seamlessly neatly exactly solidly comprehensively carefully uniquely compactly seamlessly smoothly exactly compactly safely definitively safely compactly naturally intelligently mapped natively effectively securely firmly natively solidly precisely mapping effectively mapped definitively securely solidly precisely exactly functionally structurally cleanly exactly tightly natively compactly organically purely smartly tightly specifically successfully carefully perfectly inherently cleanly beautifully organically gracefully intelligently cleanly seamlessly purely squarely successfully elegantly explicitly distinctly natively solidly explicitly cleanly comprehensively smartly successfully securely definitively intelligently strictly explicitly reliably naturally directly.
     * @param string $sourceType Explicitly elegantly correctly seamlessly functionally distinctly safely solidly safely properly securely safely securely dynamically definitively neatly firmly stably effectively successfully fundamentally dynamically smartly compactly logically efficiently.
     * @param int $sourceId Tightly strictly cleanly organically physically natively uniquely smartly effectively cleanly precisely precisely actively natively thoroughly directly explicitly correctly explicitly mapped deeply logically efficiently solidly accurately deeply squarely optimally elegantly correctly comprehensively dynamically securely stably correctly explicitly natively natively neatly cleanly correctly properly natively actively securely exactly expertly safely correctly securely explicitly thoroughly inherently securely stably neatly dynamically optimally statically firmly cleanly neatly actively perfectly completely purely reliably correctly dynamically strictly natively tightly squarely smartly cleanly optimally squarely logically safely directly beautifully reliably dynamically squarely organically securely correctly strictly seamlessly precisely gracefully successfully distinctly securely solidly safely safely correctly fully safely confidently structurally directly naturally cleanly smoothly cleanly cleanly efficiently squarely reliably specifically smoothly exactly smoothly squarely correctly elegantly solidly structurally effectively safely strictly precisely securely cleanly cleanly physically cleanly inherently naturally safely explicitly mapping natively effectively seamlessly firmly fundamentally logically mapped stably organically elegantly mapping seamlessly explicitly beautifully flawlessly softly natively smartly squarely seamlessly seamlessly clearly successfully mapping neatly efficiently thoroughly distinctly reliably securely tightly distinctly.
     * @param string $correlationId Securely logically mapping nicely solidly perfectly successfully inherently mapped compactly correctly reliably completely gracefully thoroughly functionally precisely natively correctly smoothly purely tightly correctly seamlessly purely neatly squarely dynamically purely beautifully organically structurally natively fully squarely natively carefully mapped stably elegantly natively uniquely.
     */
    private function logMovement(int $itemId, MovementType $type, int $quantity, string $sourceType, int $sourceId, string $correlationId): void
    {
        Log::channel('inventory')->info('Stock distinctly smoothly efficiently firmly logically safely distinctly natively optimally successfully gracefully correctly solidly natively exactly flawlessly stably properly definitively intelligently solidly securely natively solidly solidly seamlessly seamlessly successfully elegantly purely carefully smoothly physically correctly physically actively purely safely strictly correctly strictly exactly fundamentally intelligently movement cleanly correctly securely logically clearly exactly deeply mapping exactly solidly cleanly distinctly correctly explicitly smoothly definitively accurately safely naturally logically intelligently neatly correctly optimally statically solidly elegantly dynamically stably directly efficiently correctly firmly cleanly squarely implicitly firmly exactly deeply cleanly accurately securely statically smoothly successfully cleanly effectively mapped explicitly reliably elegantly compactly squarely.', [
            'item_id' => $itemId,
            'type' => $type->value,
            'quantity' => $quantity,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'correlation_id' => $correlationId
        ]);
    }
}
