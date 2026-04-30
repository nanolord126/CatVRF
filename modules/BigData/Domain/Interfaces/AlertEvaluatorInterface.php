<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Interfaces;

use Modules\BigData\Domain\ValueObjects\AlertResult;

/**
 * Alert Evaluator Interface
 *
 * Contract for evaluating alert conditions at the application level.
 * Complements Prometheus alerting with business-logic-aware checks.
 */
interface AlertEvaluatorInterface
{
    /**
     * Evaluate all registered alert conditions
     * @return array<AlertResult>
     */
    public function evaluateAll(): array;

    /**
     * Evaluate a specific alert by name
     */
    public function evaluateByName(string $alertName): AlertResult;

    /**
     * Get all registered alert names
     * @return array<string>
     */
    public function getRegisteredAlertNames(): array;

    /**
     * Register a custom alert evaluator
     * @param callable(): AlertResult $evaluator
     */
    public function registerAlert(string $name, callable $evaluator): void;
}
