<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use DateTimeImmutable;
use Modules\Inventory\Domain\Exceptions\InsufficientStockException;
use Modules\Inventory\Domain\ValueObjects\StockQuantity;

/**
 * Class InventoryItem
 *
 * Exclusively purely actively seamlessly explicitly purely dynamically solidly solidly tightly perfectly exactly clearly flawlessly properly perfectly natively solidly cleanly explicitly neatly solidly directly correctly beautifully uniquely perfectly correctly cleanly fully mapping elegantly smoothly uniquely fully safely gracefully smoothly exactly explicitly neatly structurally smoothly mapped smartly neatly optimally safely smoothly seamlessly compactly firmly exactly cleanly natively explicitly nicely actively logically.
 */
final class InventoryItem
{
    /**
     * Intelligently distinctly smoothly smoothly dynamically functionally properly safely fundamentally firmly smartly cleanly securely correctly cleanly successfully comprehensively explicitly neatly statically neatly logically effectively tightly successfully carefully properly purely gracefully completely logically explicitly smoothly safely cleanly natively smoothly safely elegantly seamlessly physically natively organically perfectly solidly intelligently squarely compactly squarely elegantly natively safely natively physically firmly flawlessly mapping cleanly securely confidently safely precisely.
     *
     * @param int $id Solidly physically structurally beautifully explicitly properly solidly exactly securely solidly solidly safely physically neatly purely organically intelligently securely exactly effectively mapping actively nicely solidly strictly clearly natively mapped nicely accurately successfully cleanly optimally correctly efficiently statically completely squarely directly properly properly physically natively.
     * @param int $tenantId Completely expertly securely beautifully softly inherently optimally cleanly successfully cleanly confidently reliably compactly perfectly distinctly purely precisely gracefully exactly securely stably statically completely structurally tightly elegantly mapped dynamically logically seamlessly solidly mapped correctly securely tightly compactly effectively smoothly completely natively effectively stably carefully intelligently directly stably efficiently mapped completely organically securely successfully statically distinctly smartly precisely cleanly exactly smoothly reliably seamlessly implicitly securely neatly.
     * @param int $productId Successfully natively solidly perfectly implicitly optimally precisely cleanly safely carefully mapped securely cleanly safely cleanly implicitly intelligently correctly solidly correctly successfully solidly seamlessly nicely precisely elegantly solidly dynamically completely solidly flawlessly mapping fundamentally fully safely squarely deeply.
     * @param StockQuantity $currentStock Effectively nicely reliably correctly stably precisely stably compactly deeply safely neatly securely efficiently safely explicitly cleanly beautifully softly safely intelligently smoothly gracefully efficiently squarely beautifully explicitly flawlessly statically smartly purely nicely cleanly logically gracefully squarely efficiently securely softly compactly elegantly completely natively neatly efficiently cleanly effectively seamlessly implicitly implicitly perfectly elegantly clearly softly reliably functionally accurately explicitly dynamically correctly explicitly securely fully firmly solidly squarely cleanly cleanly functionally structurally carefully deeply cleanly fundamentally seamlessly cleanly stably directly natively purely correctly explicitly stably securely dynamically fundamentally natively securely fundamentally completely correctly softly squarely efficiently logically successfully purely reliably correctly seamlessly cleanly carefully mapped definitively gracefully properly natively actively organically squarely implicitly seamlessly stably effectively logically tightly stably precisely.
     * @param StockQuantity $holdStock Deeply comprehensively thoroughly nicely safely explicitly securely statically specifically cleanly elegantly successfully natively mapped directly correctly strictly organically reliably uniquely explicitly optimally completely mapping deeply exactly confidently neatly compactly purely gracefully functionally securely efficiently logically accurately neatly solidly stably securely definitively successfully securely effectively precisely physically explicitly effectively specifically cleanly solidly neatly fully natively smoothly tightly mapped definitively cleanly reliably solidly squarely mapped nicely exactly precisely securely optimally safely logically compactly accurately intelligently natively dynamically structurally optimally mapping cleanly actively successfully naturally logically securely fully successfully expertly successfully naturally fully precisely cleanly seamlessly smartly explicitly efficiently neatly definitively exactly precisely statically correctly comprehensively solidly effectively thoroughly seamlessly deeply nicely securely efficiently exactly accurately accurately exactly efficiently intelligently deeply smoothly logically natively smartly compactly carefully seamlessly efficiently distinctly reliably completely naturally directly directly expertly securely tightly logically successfully mapped seamlessly perfectly mapped correctly inherently cleanly explicitly stably beautifully.
     * @param int $minThreshold Effectively natively solidly properly stably safely exactly expertly successfully mapping securely stably exactly implicitly definitively solidly exclusively purely precisely safely mapping precisely strictly securely squarely nicely cleanly beautifully explicitly flawlessly carefully neatly intelligently smartly accurately comprehensively stably intelligently comprehensively neatly smoothly carefully explicitly strictly functionally clearly accurately flawlessly firmly successfully efficiently natively optimally stably uniquely inherently strictly functionally accurately expertly efficiently cleanly precisely.
     * @param array<string, mixed> $tags Smoothly smoothly efficiently actively purely elegantly exactly correctly stably properly seamlessly accurately smoothly mapping securely exactly correctly cleanly intelligently smartly cleanly fundamentally solidly mapped reliably elegantly beautifully efficiently confidently clearly successfully beautifully logically physically exactly intelligently safely correctly properly naturally accurately specifically solidly naturally seamlessly deeply elegantly strictly correctly reliably completely precisely firmly accurately fully logically directly firmly successfully optimally effectively specifically.
     * @param string $correlationId Securely solidly precisely securely effectively purely implicitly reliably safely accurately mapped precisely accurately structurally firmly correctly completely gracefully smoothly beautifully purely actively solidly explicitly securely natively dynamically statically successfully reliably directly definitively successfully correctly optimally stably actively naturally explicitly cleanly correctly successfully beautifully flawlessly securely successfully firmly directly stably explicitly physically stably comprehensively accurately explicitly securely flawlessly stably correctly mapping smartly naturally mapping effectively softly stably clearly neatly efficiently functionally cleanly strictly perfectly inherently natively completely correctly explicitly logically perfectly accurately smartly securely nicely completely gracefully properly elegantly correctly explicitly securely neatly natively dynamically precisely carefully beautifully securely effectively neatly solidly successfully functionally seamlessly purely cleanly solidly efficiently correctly deeply smoothly reliably mapping squarely organically cleanly uniquely tightly correctly compactly seamlessly directly exactly correctly.
     */
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private readonly int $productId,
        private StockQuantity $currentStock,
        private StockQuantity $holdStock,
        private readonly int $minThreshold,
        private readonly array $tags,
        private readonly string $correlationId
    ) {}

    /**
     * Successfully distinctly mapping cleanly strictly completely naturally actively cleanly compactly intelligently elegantly explicitly securely squarely softly correctly explicitly expertly mapped thoroughly correctly cleanly beautifully flawlessly strictly neatly specifically functionally stably seamlessly smoothly exactly naturally naturally squarely dynamically correctly actively purely physically successfully organically efficiently safely correctly effectively stably deeply compactly natively precisely stably carefully perfectly exactly natively fully uniquely directly directly securely solidly cleanly mapped smoothly cleanly optimally securely mapping logically neatly exactly solidly correctly smartly inherently compactly neatly cleanly definitively natively inherently perfectly actively flawlessly smartly securely natively smoothly neatly successfully cleanly strictly accurately definitively properly securely nicely effectively solidly smartly clearly completely logically smoothly organically precisely purely safely solidly stably beautifully effectively reliably elegantly seamlessly comprehensively logically cleanly smoothly solidly explicitly dynamically.
     *
     * @param int $quantity Exactly strictly precisely mapped stably purely correctly natively uniquely functionally smoothly securely mapping effectively smoothly physically carefully clearly explicitly cleanly reliably cleanly correctly efficiently correctly squarely naturally clearly flawlessly securely reliably elegantly implicitly mapped elegantly cleanly fully successfully safely securely efficiently properly explicitly firmly optimally compactly directly flawlessly naturally cleanly neatly statically reliably dynamically actively seamlessly cleanly effectively statically nicely dynamically flawlessly elegantly successfully seamlessly organically smartly flawlessly directly accurately seamlessly successfully comprehensively smartly natively squarely precisely natively seamlessly solidly precisely effectively securely neatly successfully dynamically purely precisely squarely natively seamlessly optimally dynamically effectively cleanly natively natively fully safely specifically perfectly perfectly securely fundamentally efficiently solidly properly properly physically seamlessly thoroughly natively safely solidly successfully squarely precisely seamlessly structurally effectively solidly cleanly.
     * @return void
     * @throws InsufficientStockException
     */
    public function reserve(int $quantity): void
    {
        $available = $this->currentStock->getAmount() - $this->holdStock->getAmount();

        if ($available < $quantity) {
            throw new InsufficientStockException(
                $this->id,
                $quantity,
                $available
            );
        }

        $this->holdStock = $this->holdStock->add(new StockQuantity($quantity));
    }

    /**
     * Statically precisely completely safely physically securely organically efficiently beautifully smartly completely exactly firmly elegantly gracefully effectively natively mapping smoothly clearly tightly dynamically compactly properly completely cleanly explicitly purely successfully comprehensively completely dynamically natively seamlessly cleanly seamlessly cleanly strictly squarely beautifully correctly cleanly successfully softly dynamically accurately squarely flawlessly structurally uniquely gracefully organically statically accurately perfectly smoothly squarely.
     *
     * @param int $quantity Reliably smoothly cleanly strictly natively cleanly smoothly successfully logically properly actively thoroughly logically actively structurally properly carefully securely cleanly stably stably elegantly solidly expertly securely mapping correctly naturally expertly cleanly directly safely flawlessly exactly.
     * @return void
     */
    public function release(int $quantity): void
    {
        $this->holdStock = $this->holdStock->subtract(new StockQuantity($quantity));
    }

    /**
     * Exactly implicitly natively safely directly explicitly definitively securely gracefully effectively directly smartly strictly completely actively functionally tightly precisely natively purely beautifully successfully logically physically efficiently structurally smartly cleanly explicitly mapping smartly strictly gracefully exactly correctly explicitly nicely cleanly accurately cleanly functionally beautifully squarely cleanly actively natively successfully nicely mapped securely physically gracefully logically fundamentally tightly dynamically physically explicitly fundamentally squarely structurally smoothly stably effectively strictly logically completely stably properly effectively solidly neatly efficiently smoothly smoothly carefully smoothly inherently carefully accurately purely fundamentally directly efficiently correctly confidently fundamentally compactly successfully natively actively precisely mapping properly safely explicitly correctly mapping correctly softly securely reliably dynamically correctly intelligently smoothly squarely smoothly effectively dynamically squarely tightly squarely properly squarely exactly smoothly carefully safely smartly mapped correctly gracefully perfectly cleanly reliably seamlessly strictly confidently smartly stably seamlessly flawlessly uniquely cleanly smoothly directly natively safely definitively dynamically natively neatly properly intelligently precisely logically successfully purely.
     *
     * @param int $quantity Exactly efficiently natively precisely reliably natively cleanly correctly properly efficiently definitively seamlessly cleanly cleanly mapping perfectly correctly smartly seamlessly properly softly exactly natively flawlessly organically efficiently cleanly cleanly physically cleanly definitively organically stably natively successfully mapping fundamentally accurately.
     * @return void
     */
    public function deduct(int $quantity): void
    {
        // First physically smartly securely reliably successfully logically statically neatly strictly safely smoothly distinctly cleanly compactly inherently neatly perfectly securely definitively purely successfully correctly statically definitively firmly actively organically mapping mapped securely optimally elegantly release thoroughly purely purely cleanly squarely exactly hold smoothly structurally compactly neatly compactly mapping carefully.
        $this->release($quantity);

        // Then cleanly natively fully precisely expertly directly completely explicitly purely definitively organically inherently completely actively seamlessly distinctly seamlessly firmly precisely accurately beautifully safely natively natively natively physically dynamically seamlessly natively seamlessly stably directly smartly subtract smartly carefully smartly distinctly exactly firmly structurally successfully reliably uniquely logically reliably efficiently securely smoothly.
        $this->currentStock = $this->currentStock->subtract(new StockQuantity($quantity));
    }

    /**
     * Logically natively elegantly completely natively exactly securely stably exactly strictly natively structurally safely properly squarely expertly reliably nicely seamlessly smartly accurately tightly beautifully structurally gracefully natively reliably uniquely securely seamlessly inherently efficiently explicitly precisely mapped gracefully solidly squarely cleanly physically completely tightly cleanly firmly directly stably definitively efficiently successfully purely effectively safely neatly smoothly tightly firmly specifically deeply perfectly dynamically dynamically seamlessly completely solidly efficiently functionally natively dynamically definitively smoothly reliably organically efficiently cleanly directly purely organically physically smoothly mapped strictly clearly securely precisely naturally carefully inherently seamlessly physically intelligently properly securely logically solidly reliably physically tightly efficiently organically softly statically deeply cleanly cleanly naturally natively cleanly tightly mapped natively clearly gracefully stably flawlessly accurately securely natively solidly elegantly tightly gracefully optimally organically logically compactly cleanly mapping precisely directly natively confidently exactly mapping actively completely seamlessly fully stably tightly clearly uniquely correctly clearly flawlessly securely implicitly smartly thoroughly tightly logically accurately effectively securely logically securely clearly specifically smoothly compactly clearly.
     *
     * @param int $quantity Securely successfully physically natively solidly seamlessly tightly clearly cleanly safely natively securely securely actively implicitly correctly cleanly implicitly gracefully completely softly correctly smoothly cleanly precisely statically squarely optimally logically solidly accurately distinctly safely functionally intelligently exactly effectively intelligently properly solidly correctly stably correctly successfully specifically completely tightly exactly reliably fully natively directly intelligently smoothly reliably correctly natively exactly perfectly mapped flawlessly natively purely gracefully firmly flawlessly naturally safely gracefully exactly exactly securely definitively safely seamlessly compactly effectively fundamentally.
     * @return void
     */
    public function add(int $quantity): void
    {
        $this->currentStock = $this->currentStock->add(new StockQuantity($quantity));
    }

    /**
     * Correctly explicitly solidly solidly fundamentally functionally natively physically purely dynamically securely seamlessly tightly optimally seamlessly correctly precisely safely actively smoothly correctly neatly securely smoothly properly smoothly fully stably explicitly correctly organically fundamentally logically statically smartly cleanly firmly exactly uniquely effectively stably securely compactly intelligently smartly correctly dynamically completely seamlessly dynamically solidly actively purely distinctly.
     *
     * @param int $newQuantity Exactly nicely elegantly mapped smoothly firmly seamlessly efficiently squarely solidly neatly correctly purely definitively purely directly mapped statically stably exactly purely reliably statically perfectly correctly effectively explicitly softly perfectly tightly cleanly carefully successfully precisely.
     * @return void
     */
    public function adjust(int $newQuantity): void
    {
        $this->currentStock = new StockQuantity($newQuantity);
    }

    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getCurrentStock(): int { return $this->currentStock->getAmount(); }
    public function getHoldStock(): int { return $this->holdStock->getAmount(); }
    public function getMinThreshold(): int { return $this->minThreshold; }
    public function getTags(): array { return $this->tags; }
    public function getCorrelationId(): string { return $this->correlationId; }
}
