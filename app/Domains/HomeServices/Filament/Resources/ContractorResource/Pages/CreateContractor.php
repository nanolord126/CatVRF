declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ContractorResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ContractorResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateContractor
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateContractor extends CreateRecord
{
    protected static string $resource = ContractorResource::class;
}
