<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\BeautySalonResource\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautySalonResource;
use App\Services\FraudControlService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateBeautySalon extends CreateRecord
{
    protected static string $resource = BeautySalonResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $correlationId = (string) Str::uuid();
            app(FraudControlService::class)->check(
                userId: auth()->user()->id,
                operationType: 'create-beauty-salon',
                amount: 0,
                correlationId: $correlationId
            );

            Log::channel('audit')->info('Filament Page Create: BeautySalon', [
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
