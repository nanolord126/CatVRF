<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\BeautySalonResource\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautySalonResource;
use App\Services\FraudControlService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EditBeautySalon extends EditRecord
{
    protected static string $resource = BeautySalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->action(function ($record) {
                DB::transaction(function () use ($record) {
                    $correlationId = (string) Str::uuid();
                    app(FraudControlService::class)->check(
                        userId: Auth::id(),
                        operationType: 'delete-beauty-salon',
                        amount: 0,
                        ipAddress: request()->ip(),
                        correlationId: $correlationId
                    );
                    Log::channel('audit')->info('Beauty Salon deleted from Edit page', [
                        'record_id' => $record->id,
                        'tenant_id' => tenant('id'),
                        'correlation_id' => $correlationId,
                    ]);
                    $record->delete();
                });
            }),
        ];
    }
}
