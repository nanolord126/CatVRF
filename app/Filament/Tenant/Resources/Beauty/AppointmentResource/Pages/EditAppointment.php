<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\AppointmentResource\Pages;

use App\Filament\Tenant\Resources\Beauty\AppointmentResource;
use App\Services\FraudControlService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $correlationId = (string) Str::uuid();

            app(FraudControlService::class)->check(
                userId: Auth::id(),
                operationType: 'edit-appointment',
                amount: $data['total_price'] ?? 0,
                correlationId: $correlationId
            );

            Log::channel('audit')->info('Appointment updated', [
                'record_id' => $record->id,
                'data' => $data,
                'tenant_id' => tenant('id'),
                'correlation_id' => $correlationId,
            ]);

            $record->update($data);

            return $record;
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
