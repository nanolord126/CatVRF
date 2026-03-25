declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Gifts\Pages;

use App\Filament\Tenant\Resources\Gifts\GiftProductResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateGiftProduct
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateGiftProduct extends CreateRecord
{
    protected static string $resource = GiftProductResource::class;
}
