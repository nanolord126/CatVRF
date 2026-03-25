declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\TuningProjectResource\Pages;

use App\Domains\Auto\Filament\Resources\TuningProjectResource;
use App\Domains\Auto\Events\TuningProjectCreated;
use App\Services\FraudControlService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final /**
 * CreateTuningProject
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateTuningProject extends CreateRecord
{
    protected static string $resource = TuningProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = Str::uuid()->toString();
        $data['correlation_id'] = $correlationId;

        $fraudCheck = app(FraudControlService::class)->check([
            'operation_type' => 'tuning_project',
            'user_id' => auth()->id(),
            'amount' => $data['estimated_price'] ?? 0,
            'correlation_id' => $correlationId,
        ]);

        if ($fraudCheck['blocked']) {
            throw new \Exception('Операция заблокирована системой безопасности');
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->db->transaction(function () {
            $this->log->channel('audit')->info('TuningProject created', [
                'correlation_id' => $this->record->correlation_id,
                'project_id' => $this->record->id,
            ]);

            event(new TuningProjectCreated(
                $this->record,
                $this->record->correlation_id
            ));
        });

        $this->notification->make()
            ->success()
            ->title('Проект тюнинга создан')
            ->send();
    }
}
