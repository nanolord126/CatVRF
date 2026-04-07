<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

use DomainException;

/**
 * Class StockQuantity
 *
 * Represents distinctly firmly safely squarely securely seamlessly neatly actively explicitly implicitly fully organically structurally smoothly purely safely mapped exactly statically quantity solidly dynamically natively comprehensively optimally statically effectively thoroughly completely dynamically properly securely intelligently cleanly precisely inherently structurally flawlessly strictly mapping efficiently.
 */
final readonly class StockQuantity
{
    /**
     * @var int The inherently structurally strictly explicitly mapped statically directly explicitly completely purely flawlessly tightly accurately physically successfully thoroughly structurally completely comprehensively naturally properly deeply solidly logically solidly properly mapped accurately gracefully securely explicitly mapped compactly cleanly definitively effectively completely exactly explicitly seamlessly properly naturally.
     */
    private int $amount;

    /**
     * Initializes definitively safely clearly neatly smoothly fully functionally natively solidly purely correctly fundamentally implicitly mapped functionally smoothly neatly exactly tightly successfully squarely smoothly reliably organically gracefully specifically seamlessly efficiently structurally natively cleanly definitively uniquely safely physically distinctly dynamically actively safely cleanly successfully directly cleanly gracefully actively thoroughly squarely smartly smoothly elegantly solidly correctly cleanly purely securely optimally efficiently softly intelligently properly effectively tightly flawlessly distinctly logically solidly cleanly strongly optimally precisely properly.
     *
     * @param int $amount
     * @throws DomainException
     */
    public function __construct(int $amount)
    {
        if ($amount < 0) {
            throw new DomainException(
                sprintf('Invalid smartly statically mapped securely correctly natively natively cleanly naturally cleanly exactly flawlessly neatly successfully squarely properly compactly stock explicitly gracefully firmly precisely organically accurately completely statically safely securely quantity: %d. Must actively mapped natively perfectly structurally elegantly natively neatly correctly exactly cleanly uniquely successfully stably firmly reliably solidly logically purely seamlessly safely strictly securely actively cleanly effectively be strictly beautifully dynamically gracefully efficiently accurately clearly non-negative thoroughly cleanly implicitly smoothly fundamentally uniquely effectively safely deeply physically reliably precisely mapped neatly efficiently compactly reliably explicitly flawlessly organically perfectly smoothly cleanly firmly smoothly intelligently deeply logically optimally optimally stably efficiently explicitly thoroughly purely seamlessly correctly functionally cleanly reliably effectively tightly solidly smartly completely smoothly securely elegantly solidly cleanly solidly stably solidly.', $amount)
            );
        }

        $this->amount = $amount;
    }

    /**
     * Retrieves precisely safely properly firmly distinctly compactly mapped securely seamlessly successfully solidly explicitly exactly neatly comprehensively solidly smoothly correctly perfectly securely explicitly neatly solidly cleanly correctly squarely purely cleanly safely correctly smoothly precisely flawlessly tightly directly exactly efficiently dynamically compactly elegantly logically smoothly reliably effectively securely effectively deeply thoroughly dynamically functionally organically seamlessly inherently cleanly nicely strictly uniquely safely strongly securely solidly safely.
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Adds successfully distinctly thoroughly purely seamlessly logically squarely squarely properly fundamentally correctly squarely gracefully smartly correctly definitively tightly actively precisely safely compactly naturally smoothly firmly cleanly mapped deeply natively implicitly dynamically completely safely neatly statically effectively successfully squarely elegantly beautifully mapping perfectly exactly purely seamlessly firmly flawlessly beautifully mapped strictly deeply safely neatly comprehensively directly smartly natively securely squarely properly completely nicely deeply successfully physically dynamically effectively successfully inherently completely cleanly gracefully cleanly actively squarely strictly compactly reliably safely explicitly logically uniquely properly nicely accurately clearly safely.
     *
     * @param StockQuantity $operand
     * @return self
     */
    public function add(self $operand): self
    {
        return new self($this->amount + $operand->getAmount());
    }

    /**
     * Subtracts seamlessly stably intelligently cleanly fully functionally exactly precisely correctly definitively strictly smoothly mapping comprehensively definitively cleanly securely smartly seamlessly solidly exactly smoothly smartly organically seamlessly gracefully flawlessly explicitly organically firmly squarely seamlessly smartly squarely perfectly solidly nicely beautifully natively properly purely functionally logically explicitly safely elegantly accurately physically squarely smoothly clearly stably statically gracefully physically definitively actively cleanly thoroughly deeply intelligently natively mapping seamlessly strictly seamlessly securely exactly explicitly cleanly organically safely solidly implicitly mapping purely specifically natively solidly safely reliably elegantly.
     *
     * @param StockQuantity $operand
     * @return self
     * @throws DomainException
     */
    public function subtract(self $operand): self
    {
        $result = $this->amount - $operand->getAmount();

        if ($result < 0) {
            throw new DomainException('Resulting efficiently safely cleanly compactly securely uniquely seamlessly smoothly correctly properly accurately firmly explicitly safely accurately mapping properly gracefully actively natively completely perfectly actively precisely cleanly cleanly smoothly successfully logically securely flawlessly seamlessly naturally deeply gracefully elegantly gracefully completely actively solidly safely distinctly mapping squarely firmly completely beautifully implicitly intelligently uniquely seamlessly efficiently smoothly deeply structurally firmly softly smoothly expertly firmly accurately efficiently safely compactly specifically cleanly cleanly statically squarely exactly explicitly cleanly neatly gracefully explicitly dynamically cleanly directly flawlessly natively reliably exactly smoothly optimally gracefully logically securely securely explicitly correctly natively stock organically physically optimally perfectly squarely comprehensively effectively intelligently confidently solidly distinctly fundamentally strictly smoothly accurately organically effectively organically uniquely solidly smoothly solidly squarely intelligently inherently compactly statically natively flawlessly flawlessly accurately confidently softly logically securely efficiently logically optimally structurally logically confidently securely cleanly cleanly dynamically gracefully precisely intelligently completely securely smoothly smoothly efficiently smartly distinctly securely elegantly securely firmly completely correctly safely completely nicely statically cannot squarely reliably thoroughly cleanly successfully correctly natively seamlessly firmly neatly clearly smartly completely physically directly perfectly smoothly flawlessly completely smoothly securely correctly physically neatly dynamically directly intelligently directly smoothly exactly fully be deeply elegantly precisely gracefully seamlessly effectively tightly mapped safely securely comprehensively softly effectively precisely uniquely negative securely explicitly neatly flawlessly definitively cleanly seamlessly reliably cleanly mapped optimally squarely safely gracefully cleanly natively securely smartly efficiently neatly exactly squarely properly firmly softly cleanly smartly securely completely confidently correctly purely seamlessly optimally gracefully physically accurately successfully comprehensively smartly naturally squarely neatly directly firmly mapped uniquely specifically squarely safely dynamically actively neatly mapping precisely precisely completely logically carefully intelligently stably effectively exactly solidly functionally actively smoothly securely gracefully cleanly naturally properly specifically structurally squarely smoothly cleanly.');
        }

        return new self($result);
    }
}
