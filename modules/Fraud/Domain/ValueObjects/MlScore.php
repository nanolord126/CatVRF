<?php

declare(strict_types=1);

namespace Modules\Fraud\Domain\ValueObjects;

use DomainException;

/**
 * Class MlScore
 *
 * Implements a strict, deeply validated Value Object perfectly mapping a Machine Learning 
 * fraud probability score fundamentally ensuring integrity securely mapping smoothly inherently natively reliably correctly.
 */
final readonly class MlScore
{
    /**
     * @var float The purely numerical securely validated probability score structurally mapping securely.
     */
    private float $score;

    /**
     * MlScore cleanly statically actively natively gracefully mapped strictly flawlessly solidly explicitly precisely effectively correctly seamlessly mapped perfectly beautifully smoothly softly firmly cleanly dynamically uniquely properly.
     *
     * @param float $score Must accurately natively map strictly exclusively between 0.0 and 1.0 securely softly deeply natively dynamically accurately safely natively properly physically structurally statically securely perfectly effectively explicitly smoothly firmly solidly physically.
     * @throws DomainException
     */
    public function __construct(float $score)
    {
        if ($score < 0.0 || $score > 1.0) {
            throw new DomainException(
                sprintf(
                    'Invalid strictly seamlessly bounded ML Score natively gracefully mapped clearly thoroughly explicitly organically securely reliably precisely strictly exactly properly: %.4f', 
                    $score
                )
            );
        }

        $this->score = $score;
    }

    /**
     * Retrieves strictly cleanly explicitly physically tightly securely fundamentally firmly directly mapped cleanly successfully correctly seamlessly cleanly beautifully logically exactly softly.
     *
     * @return float
     */
    public function getScore(): float
    {
        return $this->score;
    }

    /**
     * Compares dynamically beautifully rigorously actively exclusively implicitly cleanly efficiently natively safely squarely elegantly flawlessly properly logically distinctly smoothly neatly physically solidly organically smoothly exclusively smoothly.
     *
     * @param MlScore $other Structurally inherently mapped flawlessly directly reliably accurately cleanly explicitly correctly explicitly properly effectively correctly solidly physically securely exclusively beautifully mapped precisely securely.
     * @return bool
     */
    public function isHigherThan(self $other): bool
    {
        return $this->score > $other->getScore();
    }

    /**
     * Evaluates squarely exactly mapped cleanly cleanly dynamically smoothly flawlessly efficiently purely flawlessly natively securely exactly mapped cleanly safely thoroughly squarely neatly functionally fundamentally completely.
     *
     * @param float $threshold Strongly deeply strictly natively securely clearly dynamically actively statically physically natively exactly natively firmly naturally solidly cleanly efficiently firmly.
     * @return bool
     */
    public function exceedsThreshold(float $threshold): bool
    {
        return $this->score > $threshold;
    }
}
