<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Interfaces;

/**
 * Metrics Exporter Interface
 *
 * Contract for rendering metrics in Prometheus exposition format.
 */
interface MetricsExporterInterface
{
    /**
     * Render all metrics in Prometheus text exposition format
     */
    public function render(): string;

    /**
     * Get the content type for HTTP response
     */
    public function getContentType(): string;

    /**
     * Refresh metric values from their sources
     */
    public function refresh(): void;
}
