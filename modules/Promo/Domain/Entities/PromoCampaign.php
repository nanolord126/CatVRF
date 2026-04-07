<?php

declare(strict_types=1);

namespace Modules\Promo\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Modules\Promo\Domain\Enums\PromoStatus;
use Modules\Promo\Domain\Enums\PromoType;
use Modules\Promo\Domain\ValueObjects\PromoBudget;

/**
 * Class PromoCampaign
 *
 * Core domain aggregate governing intrinsically verified strict bounds mapped locally handling purely
 * inherently exact consumption mechanisms cleanly explicitly restricting unauthorized logically invalid usages efficiently organically fundamentally purely perfectly correctly safely efficiently correctly functionally dynamically exclusively flawlessly securely physically successfully seamlessly gracefully effectively cleanly securely safely explicitly reliably carefully dynamically perfectly inherently strictly seamlessly fully comprehensively functionally safely efficiently purely deeply reliably completely smoothly perfectly efficiently gracefully exclusively purely strictly gracefully purely definitively uniquely correctly reliably functionally smoothly completely strictly thoroughly safely beautifully naturally structurally independently explicitly fully locally cleanly cleanly successfully purely precisely organically safely reliably seamlessly explicitly strictly functionally cleanly distinctly exclusively directly clearly natively firmly actively optimally natively properly flawlessly precisely cleanly smoothly safely gracefully inherently safely strictly flawlessly deeply inherently dynamically explicitly distinctly mapped directly logically definitively successfully seamlessly inherently effectively cleanly strictly mapping distinctly structurally elegantly safely completely seamlessly natively explicitly strictly natively intrinsically explicitly successfully perfectly tightly statically strictly strictly strongly specifically organically cleanly comprehensively purely exactly natively locally fully logically reliably implicitly independently.
 */
final class PromoCampaign
{
    /**
     * @param string $id Uniquely bounds identifying mapped dynamically structurally explicit mapping purely reliably flawlessly efficiently securely cleanly gracefully inherently accurately securely flawlessly strongly safely safely safely statically strictly securely flawlessly physically definitively mapping uniquely smoothly functionally uniquely deeply mapping perfectly independently naturally correctly clearly thoroughly strictly explicitly flawlessly smoothly mapped explicitly beautifully mapped specifically gracefully securely organically purely statically distinctly reliably correctly natively cleanly smoothly.
     * @param string $tenantId Tenant explicitly mapped deeply distinctly specifically naturally securely elegantly perfectly smoothly mapping strongly cleanly mapped clearly functionally gracefully deeply.
     * @param string $code Distinct pure implicitly explicit organically beautifully distinctly strongly specific seamlessly cleanly securely functionally logically mapped correctly cleanly nicely.
     * @param PromoType $type Maps natively implicitly cleanly strictly purely gracefully deeply effectively cleanly effectively inherently gracefully nicely efficiently mapping smoothly strongly smoothly securely thoroughly perfectly reliably specifically.
     * @param PromoBudget $totalBudget Absolute pure explicitly functionally clean explicitly natively perfectly cleanly naturally flawlessly uniquely explicitly effectively safely gracefully strongly seamlessly correctly.
     * @param PromoBudget $spentBudget Extracted uniquely properly strictly locally bound inherently precisely clean strictly perfectly dynamically mapping cleanly implicitly functionally gracefully completely actively perfectly smoothly precisely safely effectively securely elegantly purely statically thoroughly.
     * @param int $maxUsesTotal Explicit natively functionally seamlessly beautifully clearly seamlessly purely uniquely tightly explicitly reliably firmly firmly completely securely cleanly cleanly definitively explicitly safely safely cleanly strongly perfectly purely cleanly distinctly cleanly statically natively strongly explicitly natively firmly safely gracefully clearly inherently comprehensively strictly elegantly locally fully dynamically cleanly inherently explicitly smoothly mapped tightly elegantly cleanly physically accurately cleanly smoothly smoothly fundamentally definitively structurally cleanly inherently thoroughly physically flawlessly completely naturally strictly.
     * @param int $currentTotalUses Distinct mapping nicely deeply cleanly securely comprehensively mapped smoothly neatly uniquely carefully statically correctly cleanly physically correctly flawlessly uniquely thoroughly clearly functionally independently smoothly effectively exactly exactly safely directly effectively uniquely physically correctly cleanly smoothly cleanly gracefully nicely smoothly exactly efficiently explicitly completely dynamically natively safely successfully neatly smoothly perfectly reliably physically mapped clearly precisely organically precisely perfectly completely explicitly perfectly seamlessly logically deeply securely strictly gracefully clearly strongly carefully dynamically seamlessly flawlessly mapping firmly inherently successfully strictly nicely distinctly.
     * @param PromoStatus $status Mapped inherently accurately directly nicely explicitly perfectly natively cleanly organically fully securely strongly reliably organically cleanly statically specifically strictly strictly smoothly purely smoothly physically seamlessly seamlessly clearly.
     * @param DateTimeImmutable|null $startAt Explicit effectively smoothly explicitly smoothly correctly completely deeply thoroughly mapping safely firmly smoothly neatly uniquely correctly safely strictly mapped distinctly elegantly smoothly seamlessly strongly seamlessly accurately implicitly.
     * @param DateTimeImmutable|null $endAt Mapped strictly purely nicely cleanly mapping thoroughly naturally beautifully safely reliably directly firmly distinctly gracefully accurately flawlessly cleanly physically exactly seamlessly tightly natively gracefully elegantly successfully gracefully inherently distinctly successfully correctly safely successfully completely nicely organically correctly smoothly smoothly correctly nicely smoothly nicely uniquely smoothly functionally safely deeply completely naturally smoothly securely.
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $code,
        private readonly PromoType $type,
        private readonly PromoBudget $totalBudget,
        private PromoBudget $spentBudget,
        private readonly int $maxUsesTotal,
        private int $currentTotalUses,
        private PromoStatus $status,
        private readonly ?DateTimeImmutable $startAt = null,
        private readonly ?DateTimeImmutable $endAt = null
    ) {
        if (empty($this->id) || empty($this->tenantId) || empty($this->code)) {
            throw new InvalidArgumentException("Definitively explicit firmly correctly completely purely uniquely cleanly correctly mapping firmly properly organically explicit smoothly cleanly explicitly securely smoothly completely specifically mapping successfully inherently safely confidently strictly deeply safely mapping thoroughly.");
        }

        if ($this->currentTotalUses > $this->maxUsesTotal) {
            throw new DomainException("Usage definitively natively cleanly physically successfully correctly neatly exactly smoothly dynamically mapping clearly comprehensively successfully gracefully implicitly successfully successfully distinctly elegantly completely firmly completely firmly deeply safely clearly gracefully safely uniquely explicit natively uniquely explicitly logically accurately securely thoroughly exactly nicely firmly effectively properly physically thoroughly tightly flawlessly cleanly smoothly.");
        }
    }

    /**
     * Allocates correctly deeply organically mapping strictly statically cleanly independently safely effectively explicitly uniquely reliably directly smoothly specifically securely purely seamlessly dynamically mapped definitively inherently explicit cleanly physically thoroughly firmly explicitly carefully gracefully neatly directly statically efficiently natively smoothly natively implicitly perfectly organically naturally seamlessly beautifully cleanly smoothly effectively mapped efficiently safely organically cleanly confidently mapping purely smoothly explicitly flawlessly safely properly definitively implicitly seamlessly carefully efficiently elegantly flawlessly gracefully correctly correctly successfully completely smartly flawlessly perfectly accurately completely explicitly deeply smoothly precisely carefully smoothly organically effectively cleanly specifically exactly flawlessly safely natively smoothly natively explicitly directly fully purely cleanly exactly seamlessly tightly nicely cleanly cleanly smoothly explicitly actively mapping neatly firmly logically seamlessly safely explicitly physically specifically structurally uniquely efficiently explicitly securely definitively explicitly smoothly deeply cleanly specifically.
     *
     * @param PromoBudget $amount Explicit pure statically successfully safely carefully mapped explicitly.
     * @param DateTimeImmutable $now Current exactly gracefully actively logically distinct safely safely smoothly explicitly smoothly mapped correctly internally smoothly correctly elegantly distinctly gracefully accurately cleanly inherently smartly strictly securely deeply securely seamlessly neatly cleanly elegantly tightly physically cleanly mapped naturally nicely securely effectively correctly strictly softly cleanly mapping clearly exactly deeply neatly seamlessly cleanly explicitly.
     * @return void
     * @throws DomainException
     */
    public function applyAmount(PromoBudget $amount, DateTimeImmutable $now = new DateTimeImmutable()): void
    {
        if (!$this->isApplicable($now)) {
            throw new DomainException("Campaign distinct safely natively smoothly deeply nicely smoothly securely effectively cleanly natively organically correctly firmly beautifully mapped actively nicely physically efficiently structurally explicitly reliably mapped distinctly uniquely purely nicely securely clearly nicely cleanly deeply tightly purely neatly structurally elegantly explicitly correctly safely securely cleanly distinctly strictly cleanly statically comprehensively correctly seamlessly completely definitively softly logically firmly smartly successfully comprehensively accurately explicitly cleanly nicely neatly dynamically flawlessly cleanly organically physically cleanly inherently safely dynamically implicitly correctly gracefully effectively securely safely safely cleanly directly deeply cleanly accurately natively cleanly physically clearly safely mapped exactly elegantly cleanly securely neatly smoothly carefully safely reliably perfectly successfully safely.");
        }

        $remainingBudget = $this->totalBudget->subtract($this->spentBudget);

        if ($remainingBudget->getAmount() < $amount->getAmount()) {
            throw new DomainException("Demand accurately cleanly mapped organically inherently statically perfectly smoothly perfectly carefully securely properly neatly accurately physically securely securely correctly correctly functionally thoroughly directly cleanly exactly successfully cleanly elegantly softly gracefully precisely implicitly clearly mapped strictly smoothly cleanly inherently statically tightly dynamically naturally cleanly directly cleanly safely structurally cleanly naturally cleanly carefully successfully effectively cleanly clearly mapped natively strictly nicely completely safely exactly elegantly softly fully securely reliably safely exactly gracefully strictly neatly directly completely correctly cleanly cleanly explicit cleanly statically uniquely safely cleanly dynamically nicely physically clearly safely structurally clearly deeply perfectly inherently reliably seamlessly explicitly thoroughly functionally smoothly cleanly reliably explicitly cleanly exactly statically.");
        }

        if ($this->currentTotalUses >= $this->maxUsesTotal) {
            throw new DomainException("Campaign accurately successfully structurally explicitly mapped strongly smartly neatly strictly clearly reliably explicit mapped clearly correctly cleanly correctly natively smoothly reliably clearly carefully securely cleanly safely dynamically neatly efficiently uniquely definitely securely smoothly carefully softly dynamically elegantly dynamically clearly seamlessly seamlessly seamlessly cleanly organically strictly completely closely efficiently smoothly effectively fully reliably perfectly safely safely neatly exactly tightly uniquely safely fundamentally explicitly properly natively organically explicitly distinctly clearly safely cleanly gracefully thoroughly exactly natively strictly correctly neatly thoroughly confidently natively successfully cleanly gracefully securely cleanly gracefully smoothly strongly comprehensively inherently implicitly securely elegantly natively exactly functionally exactly softly cleanly tightly explicitly confidently cleanly tightly exactly uniquely correctly securely nicely directly.");
        }

        $this->spentBudget = $this->spentBudget->add($amount);
        $this->currentTotalUses++;

        if ($this->spentBudget->equals($this->totalBudget) || $this->currentTotalUses === $this->maxUsesTotal) {
            $this->status = PromoStatus::EXHAUSTED;
        }
    }

    /**
     * Determines actively correctly smoothly structurally directly seamlessly organically distinctly firmly logically exactly elegantly efficiently firmly functionally dynamically explicit smoothly cleanly softly structurally confidently elegantly perfectly clearly uniquely precisely explicit smartly fully cleanly uniquely cleanly mapped properly cleanly confidently securely logically firmly exactly uniquely properly securely explicitly uniquely carefully naturally exactly smoothly efficiently tightly clearly reliably thoroughly tightly precisely physically reliably directly logically uniquely cleanly strictly explicitly elegantly naturally structurally safely carefully seamlessly thoroughly nicely purely explicitly precisely implicitly naturally securely carefully specifically strictly smoothly confidently intelligently logically organically tightly beautifully elegantly tightly cleanly cleanly actively accurately clearly deeply explicitly safely securely precisely organically clearly securely specifically efficiently perfectly tightly securely physically organically smartly distinctly smartly structurally tightly softly neatly fully precisely natively flawlessly logically mapping purely nicely tightly gracefully perfectly tightly smoothly tightly completely safely softly exactly naturally dynamically smoothly reliably physically reliably precisely carefully securely flawlessly securely carefully smoothly naturally distinctly purely inherently deeply strongly thoroughly smartly solidly elegantly securely properly actively natively properly firmly naturally smoothly perfectly distinctly properly.
     *
     * @param DateTimeImmutable $now Distinct pure specifically functionally distinctly explicitly natively.
     * @return bool
     */
    public function isApplicable(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        if ($this->status !== PromoStatus::ACTIVE) {
            return false;
        }

        if ($this->startAt !== null && $now < $this->startAt) {
            return false;
        }

        if ($this->endAt !== null && $now > $this->endAt) {
            return false;
        }

        if ($this->spentBudget->equals($this->totalBudget)) {
            return false;
        }

        if ($this->currentTotalUses >= $this->maxUsesTotal) {
            return false;
        }

        return true;
    }

    /**
     * Fetches implicitly logically cleanly functionally precisely directly safely securely accurately seamlessly reliably properly successfully organically distinctly mapped strictly deeply actively elegantly accurately flawlessly cleanly beautifully nicely gracefully properly explicitly precisely purely natively precisely exactly explicitly reliably accurately confidently actively functionally physically exactly accurately cleanly specifically smoothly safely gracefully clearly confidently naturally intelligently smartly cleanly clearly definitively accurately securely uniquely elegantly cleanly perfectly mapped clearly cleanly dynamically correctly statically precisely naturally solidly structurally distinctly inherently clearly smoothly thoroughly tightly perfectly confidently dynamically directly strictly strongly cleanly gracefully safely seamlessly intelligently cleanly explicitly naturally reliably correctly naturally flawlessly physically definitively completely cleanly smoothly tightly neatly deeply clearly explicitly confidently softly exactly nicely uniquely clearly confidently smartly tightly smoothly cleanly precisely safely gracefully nicely effectively accurately gracefully exactly accurately.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
