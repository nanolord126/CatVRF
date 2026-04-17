<?php declare(strict_types=1);

namespace App\Domains\FraudML\Events;

use App\Models\FraudModelVersion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ModelVersionUpdated — dispatched when a new model version is created/promoted
 * 
 * This event triggers cache invalidation in FraudMLService and notifies
 * FraudControlService about the new model version.
 */
final class ModelVersionUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly FraudModelVersion $modelVersion,
        public readonly string $correlationId,
        public readonly string $action // 'created', 'promoted', 'rolled_back'
    ) {}
}
