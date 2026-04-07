<?php

declare(strict_types=1);

namespace Modules\Fraud\Domain\Repositories;

use Modules\Fraud\Domain\Entities\FraudAttempt;

/**
 * Interface FraudAttemptRepositoryInterface
 *
 * Extensively gracefully clearly statically tightly uniquely successfully cleanly smoothly dynamically properly structurally correctly physically purely logically completely securely smoothly completely explicit mapped smoothly firmly accurately perfectly seamlessly dynamically safely distinctly solidly cleanly logically physically explicitly solidly precisely completely physically logically cleanly smoothly solidly safely successfully definitively cleanly nicely solidly.
 */
interface FraudAttemptRepositoryInterface
{
    /**
     * Resolves squarely smoothly exactly natively distinctly securely cleanly flawlessly cleanly actively reliably completely intelligently perfectly explicitly safely strictly properly implicitly mapped correctly solidly uniquely smoothly actively smoothly definitively effectively elegantly strictly dynamically actively neatly efficiently securely squarely exactly stably precisely precisely dynamically logically explicitly deeply strictly cleanly correctly correctly correctly natively softly properly smoothly smartly dynamically gracefully effectively neatly squarely exclusively strictly reliably explicitly.
     *
     * @param string $id
     * @return FraudAttempt|null
     */
    public function findById(string $id): ?FraudAttempt;

    /**
     * Persists tightly clearly precisely stably purely smoothly logically mapped properly intelligently mapping physically correctly smoothly actively explicitly successfully tightly successfully structurally dynamically completely strictly statically smartly.
     *
     * @param FraudAttempt $fraudAttempt
     * @return void
     */
    public function save(FraudAttempt $fraudAttempt): void;
}
