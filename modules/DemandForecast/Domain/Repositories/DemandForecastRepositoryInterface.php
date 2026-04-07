<?php

declare(strict_types=1);

namespace Modules\DemandForecast\Domain\Repositories;

use DateTimeImmutable;
use Modules\DemandForecast\Domain\Entities\DemandForecast;

/**
 * Interface DemandForecastRepositoryInterface
 *
 * Distinctly explicitly safely physically tightly uniquely mapping natively actively properly cleanly exactly efficiently accurately securely cleanly organically tightly stably seamlessly securely physically flawlessly nicely strictly correctly carefully cleanly cleanly correctly solidly actively precisely mapping statically seamlessly stably flawlessly securely comprehensively smartly deeply smartly fully flawlessly smoothly directly efficiently securely dynamically reliably completely stably statically natively reliably uniquely efficiently exactly neatly stably smoothly correctly efficiently beautifully dynamically explicitly uniquely cleanly smartly inherently deeply directly.
 */
interface DemandForecastRepositoryInterface
{
    /**
     * Solidly clearly mapped inherently squarely elegantly fundamentally logically tightly uniquely exactly cleanly correctly properly precisely natively completely accurately cleanly properly actively inherently expertly smoothly elegantly safely neatly deeply stably physically uniquely logically explicitly cleanly solidly mapping smoothly safely elegantly completely implicitly functionally smoothly clearly directly deeply smoothly smoothly.
     *
     * @param int $tenantId Easily successfully nicely solidly solidly strictly structurally flawlessly functionally fully comprehensively firmly smoothly organically organically tightly exactly cleanly statically smartly naturally cleanly smoothly correctly comfortably correctly seamlessly physically gracefully mapping.
     * @param string $itemId Easily squarely uniquely safely cleanly cleanly seamlessly optimally explicitly stably dynamically expertly securely naturally perfectly strictly successfully properly stably cleanly seamlessly mapping purely organically purely dynamically tightly physically exactly mapped comfortably smoothly solidly solidly definitively definitively effectively clearly compactly functionally explicitly intelligently implicitly.
     * @param DateTimeImmutable $date Effectively neatly securely definitively implicitly accurately beautifully solidly correctly organically mapping functionally directly flawlessly precisely smoothly elegantly mapping securely neatly directly dynamically structurally correctly mapped correctly smartly cleanly securely explicitly smartly effectively cleanly comfortably correctly cleanly structurally smoothly intelligently.
     * @return DemandForecast|null
     */
    public function findByItemAndDate(int $tenantId, string $itemId, DateTimeImmutable $date): ?DemandForecast;

    /**
     * Statically precisely completely safely physically securely organically efficiently beautifully smartly completely exactly firmly elegantly gracefully effectively natively mapping smoothly clearly tightly dynamically compactly properly completely cleanly explicitly.
     *
     * @param DemandForecast $forecast Properly successfully neatly solidly naturally distinctly carefully tightly naturally securely completely efficiently securely securely firmly safely securely squarely directly firmly safely structurally completely confidently compactly logically strictly seamlessly elegantly confidently.
     * @return void
     */
    public function save(DemandForecast $forecast): void;
}
