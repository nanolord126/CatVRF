<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PharmacyOrderCancelled
{
    use Dispatchable, SerializesModels;
    public function __construct(public readonly string $correlationId, public readonly mixed $order) {}
}
