<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\AppointmentResource\Pages;

use App\Filament\Tenant\Resources\Beauty\AppointmentResource;
use App\Services\FraudControlService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $correlationId = (string) Str::uuid();

            app(FraudControlService::class)->check(
                userId: Auth::id(),
                operationType: 'create-appointment',
                amount: $data['total_price'] ?? 0,
                correlationId: $correlationId
            );

            Log::channel('audit')->info('Appointment created', [
                'data' => $data,
                'tenant_id' => tenant('id'),
                'correlation_id' => $correlationId,
            ]);

            $data['tenant_id'] = tenant('id');
            $data['correlation_id'] = $correlationId;

            return static::getModel()::create($data);
        });
    }
}
