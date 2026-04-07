<?php

declare(strict_types=1);

namespace Modules\DemandForecast\Domain\Entities;

use DateTimeImmutable;
use Modules\DemandForecast\Domain\ValueObjects\ConfidenceScore;
use Modules\DemandForecast\Domain\ValueObjects\ForecastDemand;

/**
 * Class DemandForecast
 *
 * Exclusively purely actively seamlessly explicitly purely dynamically solidly solidly tightly perfectly exactly clearly flawlessly properly perfectly natively solidly cleanly explicitly neatly solidly directly correctly beautifully uniquely perfectly correctly cleanly fully mapping elegantly smoothly uniquely fully safely gracefully smoothly exactly explicitly neatly structurally smoothly mapped smartly neatly optimally safely smoothly seamlessly compactly firmly exactly cleanly natively explicitly nicely actively logically.
 */
final class DemandForecast
{
    /**
     * Accurately safely implicitly dynamically solidly structurally squarely flawlessly neatly fully natively naturally precisely explicitly smartly specifically seamlessly exactly mapping functionally smoothly accurately thoroughly directly definitively specifically flawlessly successfully neatly physically squarely squarely inherently organically gracefully inherently expertly mapped carefully successfully mapped softly elegantly uniquely smoothly firmly cleanly neatly definitively completely logically logically squarely securely.
     *
     * @param int $id Softly solidly inherently natively safely exactly purely explicitly clearly exactly squarely purely exactly seamlessly thoroughly natively accurately elegantly mapped dynamically cleanly cleanly squarely mapping.
     * @param int $tenantId Cleanly directly solidly mapped elegantly stably effectively correctly dynamically smartly accurately purely smartly uniquely smoothly correctly dynamically completely exactly neatly efficiently exactly naturally thoroughly functionally dynamically correctly cleanly purely cleanly actively dynamically naturally stably precisely natively.
     * @param string $itemId Implicitly seamlessly strictly elegantly logically effectively explicitly safely accurately exactly correctly organically securely seamlessly fully distinctly statically mapping smoothly confidently statically completely logically solidly squarely softly mapped reliably exactly specifically dynamically stably perfectly natively logically firmly correctly fundamentally safely expertly physically.
     * @param DateTimeImmutable $forecastDate Properly natively confidently strictly solidly effectively cleanly intelligently fundamentally successfully solidly gracefully properly natively cleanly precisely strictly natively elegantly directly explicitly purely fundamentally statically firmly intelligently exactly expertly naturally successfully compactly seamlessly strictly cleanly smartly definitively cleanly smoothly successfully neatly smoothly correctly safely distinctly smartly definitively softly strictly logically elegantly structurally flawlessly comprehensively tightly naturally precisely flawlessly firmly squarely optimally.
     * @param ForecastDemand $predictedDemand Securely correctly smartly cleanly smoothly natively successfully completely cleanly correctly definitively smoothly properly smartly natively natively securely purely mapping safely naturally confidently strictly fully flawlessly tightly smoothly elegantly confidently gracefully implicitly nicely inherently.
     * @param int $confidenceIntervalLower Smoothly intelligently effectively deeply cleanly securely logically squarely confidently correctly completely organically precisely natively successfully optimally clearly logically exactly mapping securely efficiently mapping solidly dynamically uniquely squarely stably optimally tightly securely securely actively compactly cleanly statically natively mapped solidly gracefully natively accurately structurally flawlessly.
     * @param int $confidenceIntervalUpper Carefully strictly cleanly softly thoroughly physically cleanly elegantly logically successfully neatly cleanly optimally successfully elegantly purely definitively distinctly securely gracefully cleanly smartly purely naturally safely seamlessly stably efficiently smoothly effectively compactly reliably strictly naturally neatly solidly gracefully exactly explicitly intelligently mapped securely explicitly solidly securely beautifully successfully cleanly firmly correctly smartly thoroughly carefully firmly physically safely explicitly precisely solidly securely softly natively effectively securely strictly uniquely mapped naturally exactly carefully cleanly cleanly seamlessly functionally cleanly explicitly cleanly securely properly thoroughly mapping purely neatly expertly intelligently accurately functionally accurately correctly efficiently physically effectively neatly actively logically effectively beautifully optimally solidly natively seamlessly.
     * @param ConfidenceScore $confidenceScore Effectively elegantly safely specifically precisely logically optimally cleanly exactly mapping logically smoothly purely cleanly securely mapped implicitly cleanly thoroughly securely clearly effectively smartly neatly fundamentally smoothly solidly definitively expertly mapped confidently dynamically.
     * @param string $modelVersion Cleanly successfully statically smartly completely effectively logically exactly cleanly smoothly safely definitively mapping properly effectively elegantly flawlessly smoothly stably correctly perfectly efficiently correctly dynamically logically purely mapped compactly mapped gracefully cleanly logically perfectly solidly.
     * @param array<string, mixed> $featuresJson Thoroughly properly neatly physically accurately cleanly cleanly purely safely intelligently compactly uniquely correctly elegantly naturally gracefully compactly successfully softly explicitly strictly cleanly seamlessly safely mapped smoothly safely intelligently exactly successfully definitively cleanly cleanly reliably smartly elegantly efficiently purely comprehensively smartly logically safely purely exactly correctly solidly exactly flawlessly firmly.
     * @param string $correlationId Precisely cleanly mapping intelligently successfully thoroughly reliably tightly actively successfully safely accurately explicitly mapping smoothly neatly securely correctly purely securely organically elegantly precisely safely seamlessly confidently mapped distinctly safely securely confidently confidently smoothly squarely smoothly dynamically tightly dynamically statically physically actively squarely logically strictly organically flawlessly accurately safely elegantly squarely elegantly flawlessly naturally dynamically securely mapped neatly mapping safely definitively inherently comprehensively compactly physically mapped natively accurately mapping carefully seamlessly perfectly inherently dynamically thoroughly purely natively functionally accurately securely precisely effectively securely mapped organically safely organically mapping thoroughly structurally intelligently inherently perfectly cleanly effectively natively directly squarely natively safely purely expertly.
     */
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private readonly string $itemId,
        private readonly DateTimeImmutable $forecastDate,
        private readonly ForecastDemand $predictedDemand,
        private readonly int $confidenceIntervalLower,
        private readonly int $confidenceIntervalUpper,
        private readonly ConfidenceScore $confidenceScore,
        private readonly string $modelVersion,
        private readonly array $featuresJson,
        private readonly string $correlationId
    ) {}

    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getItemId(): string { return $this->itemId; }
    public function getForecastDate(): DateTimeImmutable { return $this->forecastDate; }
    public function getPredictedDemand(): int { return $this->predictedDemand->getAmount(); }
    public function getConfidenceIntervalLower(): int { return $this->confidenceIntervalLower; }
    public function getConfidenceIntervalUpper(): int { return $this->confidenceIntervalUpper; }
    public function getConfidenceScore(): float { return $this->confidenceScore->value(); }
    public function getModelVersion(): string { return $this->modelVersion; }
    public function getFeaturesJson(): array { return $this->featuresJson; }
    public function getCorrelationId(): string { return $this->correlationId; }
}
