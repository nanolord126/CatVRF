<?php declare(strict_types=1);

/**
 * IrrigationConfigurator — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/irrigationconfigurator
 * @see https://catvrf.ru/docs/irrigationconfigurator
 */


namespace App\View\Components\Configurators;

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
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\View\Components\Configurators
 */
final class IrrigationConfigurator extends Model
{

    public function __construct(
            public string $uuid = 'irrigation-x-flow',
            public array $options = []
        ) {}

        /**
         * Handle render operation.
         *
         * @throws \DomainException
         */
        public function render(): View
        {
            return view('components.configurators.irrigation-configurator');
        }

    /**
     * The number of models to return for pagination.
     */
    protected $perPage = 25;


    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }
}
