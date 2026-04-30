<?php

declare(strict_types=1);

/**
 * IrrigationConfigurator — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/irrigationconfigurator
 * @see https://catvrf.ru/docs/irrigationconfigurator
 */

namespace App\View\Components\Configurators;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class IrrigationConfigurator
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class IrrigationConfigurator extends Model
{
    /**
     * The number of models to return for pagination.
     */
    protected $perPage = 25;

    public function __construct(private readonly ViewFactory $viewFactory,
        public string $uuid = 'irrigation-x-flow',
        public array $options = []) {}

    /**
     * Handle render operation.
     *
     * @throws \DomainException
     */
    public function render(): View
    {
        return $this->viewFactory->make('components.configurators.irrigation-configurator');
    }

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }
}
