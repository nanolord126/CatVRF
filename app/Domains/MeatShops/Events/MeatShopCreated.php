<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MeatShopCreated
{
    use Dispatchable, SerializesModels;
    public function __construct(public readonly string $correlationId, public readonly mixed $shop) {}
}
