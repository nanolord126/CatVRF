<?php

declare(strict_types=1);


namespace App\Domains\MeatShops\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * MeatShopCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MeatShopCreated
{
    use Dispatchable, SerializesModels;
    public function __construct(public readonly string $correlationId, public readonly mixed $shop) {}
}
