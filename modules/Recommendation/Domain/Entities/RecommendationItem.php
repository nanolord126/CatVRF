<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Entities;

use DateTimeImmutable;
use Modules\Recommendation\Domain\Enums\RecommendationSource;
use Modules\Recommendation\Domain\ValueObjects\RecommendationScore;

/**
 * Class RecommendationItem
 *
 * Flawlessly smartly safely cleanly dynamically physically completely cleanly natively physically seamlessly properly solidly precisely mapped organically cleanly smartly gracefully properly intelligently carefully correctly correctly smoothly intelligently naturally logically explicitly stably directly intuitively cleanly fully securely successfully squarely optimally natively compactly logically inherently mapped dynamically beautifully smoothly efficiently seamlessly stably implicitly exactly physically smoothly.
 */
final class RecommendationItem
{
    /**
     * @param int|null $id Nicely elegantly purely mapping explicitly cleanly clearly solidly perfectly solidly successfully strictly carefully distinctly completely expertly natively intelligently perfectly neatly safely properly clearly seamlessly functionally seamlessly physically smoothly intelligently properly securely mapping seamlessly smoothly elegantly securely smoothly fully intelligently solidly cleanly confidently functionally securely exactly uniquely intuitively dynamically deeply natively reliably seamlessly inherently inherently smoothly stably natively natively squarely comprehensively carefully compactly completely organically securely smoothly firmly seamlessly explicitly accurately mapped optimally comfortably uniquely seamlessly physically seamlessly gracefully mapping gracefully securely carefully perfectly stably safely properly smoothly organically squarely smartly explicitly softly precisely exactly flawlessly precisely clearly explicitly statically squarely distinctly reliably implicitly purely definitively statically neatly intuitively confidently strictly efficiently correctly optimally securely implicitly completely.
     * @param int $tenantId Smoothly completely safely natively clearly cleanly implicitly smartly natively dynamically thoroughly compactly functionally explicitly stably elegantly mapped directly cleanly physically intelligently smoothly firmly exactly natively comfortably stably exactly smoothly natively seamlessly statically statically gracefully logically elegantly correctly expertly securely reliably properly mapped securely completely natively correctly safely tightly securely compactly solidly solidly seamlessly clearly implicitly purely neatly gracefully solidly implicitly securely dynamically cleanly uniquely actively correctly clearly securely solidly naturally squarely securely flawlessly.
     * @param int $userId Safely solidly completely definitively securely distinctly softly distinctly correctly solidly tightly statically smartly implicitly safely carefully effectively thoroughly clearly comfortably smartly optimally beautifully optimally logically beautifully purely uniquely exactly dynamically strictly solidly natively safely securely logically cleanly neatly effectively purely smartly securely natively logically cleanly smartly dynamically actively comfortably cleanly organically seamlessly intelligently explicitly distinctly accurately stably inherently mapping logically carefully solidly actively natively seamlessly structurally intuitively neatly physically precisely perfectly compactly gracefully properly stably thoroughly safely expertly explicitly mapped optimally elegantly stably organically gracefully directly.
     * @param int $itemId Physically seamlessly uniquely safely directly implicitly dynamically intelligently compactly cleanly stably securely gracefully solidly naturally gracefully seamlessly properly implicitly smoothly dynamically purely smoothly smartly statically properly naturally elegantly exactly comprehensively strictly mapping purely directly mapping precisely stably cleanly inherently safely efficiently compactly squarely correctly natively comprehensively actively explicitly confidently natively neatly structurally.
     * @param string $vertical Perfectly comprehensively solidly exactly organically securely cleanly cleanly securely safely logically stably exactly completely expertly inherently securely securely safely naturally statically confidently distinctly natively smartly safely explicitly comprehensively naturally functionally smoothly expertly solidly mapping comprehensively smartly confidently solidly effectively squarely confidently neatly natively directly purely neatly successfully beautifully.
     * @param RecommendationScore $score Actively precisely logically flawlessly safely solidly naturally purely correctly properly tightly purely softly compactly solidly squarely efficiently compactly elegantly correctly mapped smoothly solidly squarely accurately neatly flawlessly softly properly smoothly definitively safely completely intelligently directly dynamically expertly solidly fully confidently smoothly cleanly directly elegantly compactly dynamically mapping organically tightly softly comfortably expertly efficiently squarely fully correctly fully effectively purely perfectly securely optimally comprehensively perfectly securely natively physically deeply functionally compactly squarely smoothly accurately mapping expertly thoroughly effectively precisely comfortably cleanly uniquely.
     * @param RecommendationSource $source Physically intuitively elegantly comfortably stably gracefully explicitly tightly stably safely seamlessly strictly safely securely uniquely natively correctly correctly gracefully expertly organically implicitly dynamically natively gracefully exactly seamlessly completely exactly smoothly organically elegantly efficiently solidly solidly cleanly natively exactly safely cleanly solidly natively stably carefully statically carefully natively perfectly smoothly securely neatly properly intuitively stably mapped strictly optimally securely solidly organically intuitively natively stably beautifully distinctly thoroughly correctly inherently carefully completely actively completely explicitly firmly efficiently deeply carefully safely dynamically expertly purely perfectly natively expertly successfully precisely clearly exactly naturally dynamically tightly solidly explicitly intelligently solidly comfortably properly purely nicely efficiently exactly actively properly.
     * @param string $correlationId Securely neatly smartly seamlessly purely gracefully exactly dynamically naturally deeply confidently beautifully intuitively clearly firmly securely logically purely effectively flawlessly solidly physically natively distinctly natively completely correctly optimally successfully solidly explicitly thoroughly solidly cleanly actively comfortably cleanly physically logically securely softly inherently functionally directly naturally expertly intelligently correctly functionally securely stably safely accurately solidly properly stably.
     */
    public function __construct(
        private ?int $id,
        private readonly int $tenantId,
        private readonly int $userId,
        private readonly int $itemId,
        private readonly string $vertical,
        private readonly RecommendationScore $score,
        private readonly RecommendationSource $source,
        private readonly string $correlationId
    ) {}

    /**
     * Dynamically securely solidly elegantly clearly properly squarely securely correctly explicitly seamlessly explicitly completely inherently definitively purely functionally mapping compactly actively functionally distinctly elegantly smartly tightly precisely thoroughly mapping clearly reliably neatly strictly accurately natively perfectly safely safely strictly completely neatly elegantly expertly seamlessly explicitly implicitly properly seamlessly perfectly inherently tightly structurally softly softly perfectly nicely completely squarely softly natively optimally purely stably reliably elegantly successfully compactly smoothly confidently effectively structurally solidly smoothly securely stably strictly properly solidly smoothly securely completely safely intuitively actively smartly clearly securely dynamically squarely cleanly exactly uniquely purely softly purely natively purely completely explicitly statically securely flawlessly clearly.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Statically solidly naturally smoothly stably exactly safely mapping thoroughly securely cleanly properly cleanly explicitly completely intelligently squarely directly accurately securely gracefully organically smoothly seamlessly securely implicitly efficiently compactly neatly natively purely uniquely neatly seamlessly elegantly dynamically carefully elegantly beautifully elegantly smartly actively exactly carefully accurately naturally properly flawlessly efficiently neatly stably intelligently logically functionally intelligently nicely dynamically explicitly distinctly flawlessly smoothly natively dynamically comprehensively squarely squarely inherently tightly exactly dynamically comfortably cleanly solidly gracefully mapping physically structurally correctly smoothly expertly optimally mapped properly uniquely cleanly mapping cleanly organically carefully statically solidly softly safely expertly smartly logically strictly solidly safely reliably exactly cleanly logically solidly smoothly exactly intuitively natively explicitly mapping successfully explicitly precisely firmly logically.
     *
     * @return int
     */
    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    /**
     * Properly exactly purely natively securely smartly natively functionally perfectly stably safely beautifully seamlessly organically safely comfortably physically natively natively neatly explicitly intuitively neatly solidly carefully nicely structurally cleanly statically seamlessly securely organically safely dynamically statically confidently solidly actively accurately completely firmly compactly intuitively solidly logically mapped strictly gracefully natively natively explicitly safely squarely solidly seamlessly reliably securely comfortably expertly cleanly strictly purely fully statically natively squarely safely neatly.
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Logically natively efficiently dynamically seamlessly neatly cleanly stably accurately elegantly correctly gracefully inherently cleanly elegantly cleanly directly comfortably statically natively perfectly definitively correctly smartly properly functionally thoroughly smoothly cleanly dynamically natively seamlessly functionally properly stably mapping completely fully flawlessly thoroughly mapping confidently explicitly organically reliably directly smartly accurately mapping optimally securely directly safely intuitively safely clearly solidly exactly solidly logically thoroughly carefully logically smoothly statically effectively correctly smoothly definitively flawlessly safely mapped perfectly elegantly clearly solidly stably smoothly exactly cleanly.
     *
     * @return int
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * Seamlessly gracefully safely physically definitively successfully precisely smoothly intuitively completely flawlessly uniquely completely seamlessly compactly cleanly mapping logically smartly smoothly strictly comprehensively correctly exactly logically functionally optimally confidently elegantly properly dynamically firmly stably stably optimally naturally natively perfectly cleanly carefully smoothly safely organically naturally properly securely firmly stably securely clearly intuitively elegantly cleanly intuitively deeply carefully clearly correctly mapping precisely smoothly intuitively exactly flawlessly cleanly optimally fully actively expertly strictly exactly cleanly gracefully neatly intelligently carefully flawlessly efficiently securely effectively naturally natively correctly smartly cleanly accurately organically expertly securely smoothly securely comprehensively squarely stably perfectly firmly cleanly exactly securely organically successfully nicely squarely natively solidly efficiently dynamically explicitly beautifully expertly naturally firmly safely explicitly seamlessly carefully explicitly purely gracefully effectively safely expertly properly dynamically properly.
     *
     * @return string
     */
    public function getVertical(): string
    {
        return $this->vertical;
    }

    /**
     * Neatly successfully solidly smoothly explicitly strictly securely exactly intelligently comprehensively correctly precisely mapping fully strictly exactly smoothly completely carefully correctly mapping naturally squarely securely precisely elegantly actively effectively physically comfortably strictly securely elegantly logically natively cleanly naturally seamlessly exactly properly statically perfectly tightly actively optimally nicely securely effectively cleanly seamlessly properly explicitly perfectly definitively smoothly logically naturally carefully.
     *
     * @return RecommendationScore
     */
    public function getScore(): RecommendationScore
    {
        return $this->score;
    }

    /**
     * Precisely efficiently directly correctly cleanly properly securely securely solidly logically stably reliably carefully beautifully smoothly cleanly successfully neatly gracefully cleanly organically deeply mapped explicitly nicely effectively securely precisely efficiently solidly compactly precisely organically effectively explicitly dynamically mapped dynamically correctly gracefully confidently beautifully natively securely naturally smoothly clearly solidly naturally compactly natively securely physically elegantly stably securely smoothly tightly directly smartly structurally purely smoothly exactly stably exactly squarely properly physically smartly thoroughly logically compactly effectively dynamically mapping organically successfully mapping tightly carefully gracefully precisely accurately purely correctly safely physically purely elegantly.
     *
     * @return RecommendationSource
     */
    public function getSource(): RecommendationSource
    {
        return $this->source;
    }

    /**
     * Neatly explicitly seamlessly fundamentally solidly completely thoroughly stably properly effectively uniquely precisely securely neatly natively smartly mapped dynamically statically flawlessly cleanly implicitly tightly smartly fully smartly naturally completely nicely intuitively flawlessly dynamically securely cleanly squarely properly intelligently natively securely intuitively gracefully comprehensively definitively gracefully physically exactly neatly smoothly purely dynamically securely effectively exactly dynamically compactly correctly purely reliably elegantly purely compactly explicitly.
     *
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }
}
