declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\PartWarrantyResource\Pages;

use App\Domains\Auto\Filament\Resources\PartWarrantyResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final /**
 * CreatePartWarranty
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreatePartWarranty extends CreateRecord
{
    protected static string $resource = PartWarrantyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = Str::uuid()->toString();
        $data['correlation_id'] = $correlationId;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->db->transaction(function () {
            $this->log->channel('audit')->info('PartWarranty created', [
                'correlation_id' => $this->record->correlation_id,
                'warranty_id' => $this->record->id,
                'warranty_number' => $this->record->warranty_number,
            ]);
        });

        $this->notification->make()
            ->success()
            ->title('Гарантия оформлена')
            ->body('Номер гарантии: ' . $this->record->warranty_number)
            ->send();
    }
}
