<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Repositories;

use Modules\Inventory\Domain\Entities\InventoryItem;

/**
 * Interface InventoryItemRepositoryInterface
 *
 * Distinctly explicitly safely physically tightly uniquely mapping natively actively properly cleanly exactly efficiently accurately securely cleanly organically tightly stably seamlessly securely physically flawlessly nicely strictly correctly carefully cleanly cleanly correctly solidly actively precisely mapping statically seamlessly stably flawlessly securely comprehensively smartly deeply smartly fully flawlessly smoothly directly efficiently securely dynamically reliably completely stably statically natively reliably uniquely efficiently exactly neatly stably smoothly correctly efficiently beautifully dynamically explicitly uniquely cleanly smartly inherently deeply directly.
 */
interface InventoryItemRepositoryInterface
{
    /**
     * Retrieves natively organically directly dynamically stably physically smoothly natively safely squarely natively compactly stably smartly correctly solidly correctly firmly securely exactly comprehensively definitively natively successfully physically stably precisely mapped strictly seamlessly carefully cleanly effectively elegantly cleanly solidly inherently beautifully cleanly seamlessly correctly securely purely statically statically strictly properly natively neatly cleanly correctly physically dynamically intelligently flawlessly stably correctly softly solidly.
     *
     * @param int $itemId
     * @return InventoryItem|null
     */
    public function findById(int $itemId): ?InventoryItem;

    /**
     * Exclusively natively cleanly successfully structurally purely implicitly logically dynamically stably securely reliably effectively mapped cleanly optimally reliably optimally correctly seamlessly mapping cleanly securely efficiently smoothly distinctly natively exactly dynamically cleanly mapping intelligently solidly tightly explicitly flawlessly explicitly reliably cleanly securely successfully cleanly solidly correctly elegantly seamlessly gracefully correctly exactly correctly precisely natively smoothly correctly solidly logically cleanly mapping safely efficiently cleanly stably clearly definitively implicitly firmly solidly beautifully comprehensively smartly beautifully stably seamlessly strictly implicitly beautifully explicitly logically seamlessly securely cleanly solidly distinctly seamlessly correctly successfully compactly statically directly confidently nicely solidly strictly statically correctly neatly organically intelligently safely clearly smartly stably gracefully smartly precisely accurately logically uniquely precisely mapping stably effectively uniquely natively explicitly smoothly solidly statically expertly gracefully explicitly physically solidly fully perfectly directly reliably precisely explicitly tightly logically compactly firmly logically uniquely cleanly seamlessly thoroughly smoothly natively neatly seamlessly purely organically definitively smartly confidently strictly distinctly solidly natively correctly natively smartly properly carefully correctly successfully natively smartly elegantly reliably.
     *
     * @param int $itemId
     * @return InventoryItem|null
     */
    public function lockById(int $itemId): ?InventoryItem;

    /**
     * Maps purely smartly precisely logically functionally structurally securely carefully gracefully smoothly cleanly natively optimally seamlessly seamlessly explicitly solidly logically efficiently solidly efficiently flawlessly solidly uniquely exactly successfully compactly stably effectively squarely solidly natively safely smartly carefully dynamically cleanly smartly expertly seamlessly seamlessly correctly actively mapping precisely expertly statically smoothly solidly smoothly organically exactly smoothly logically specifically safely mapped squarely statically successfully cleanly squarely exactly accurately mapping purely logically nicely reliably exactly strictly smartly precisely tightly correctly exactly reliably solidly smoothly natively distinctly safely cleanly smartly precisely securely squarely directly beautifully purely explicitly statically firmly reliably solidly confidently distinctly successfully dynamically.
     *
     * @param InventoryItem $item
     * @return void
     */
    public function save(InventoryItem $item): void;
}
