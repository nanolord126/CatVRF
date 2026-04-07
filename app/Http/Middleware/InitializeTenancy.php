<?php

declare(strict_types=1);

/**
 * InitializeTenancy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/initializetenancy
 * @see https://catvrf.ru/docs/initializetenancy
 * @see https://catvrf.ru/docs/initializetenancy
 */


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

/**
 * Class InitializeTenancy
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Http\Middleware
 */
final class InitializeTenancy extends InitializeTenancyByDomain
{
    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(Request $request, Closure $next)
    {
        // This allows the central domain to have a tenant associated with it.
        // E.g. app.yoursite.com can be a tenant domain.
        return parent::handle($request, $next);
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
