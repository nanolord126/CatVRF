declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MasterResource\Pages;

use App\Filament\Tenant\Resources\MasterResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final /**
 * CreateMaster
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateMaster extends CreateRecord
{
    protected static string $resource = MasterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = Str::uuid()->toString();
        $data['correlation_id'] = Str::uuid()->toString();

        return $data;
    }
}
