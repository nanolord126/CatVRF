<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\BeautySalonResource\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautySalonResource;
use App\Services\FraudControlService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * EditBeautySalon — редактирование салона красоты.
 *
 * Filament Tenant Panel page.
 * Fraud-check + DB::transaction + correlation_id + audit log.
 *
 * CANON 2026: сервисы резолвятся через app(), no facades.
 *
 * @package CatVRF\Filament\Tenant
 * @version 2026.1
 */
final class EditBeautySalon extends EditRecord
{
    protected static string $resource = BeautySalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function ($record): void {
                    /** @var \Illuminate\Database\DatabaseManager $db */
                    $db = app(\Illuminate\Database\DatabaseManager::class);

                    /** @var FraudControlService $fraud */
                    $fraud = app(FraudControlService::class);

                    /** @var LoggerInterface $logger */
                    $logger = app(LoggerInterface::class);

                    $correlationId = Str::uuid()->toString();

                    $db->transaction(static function () use ($record, $fraud, $logger, $correlationId): void {
                        $fraud->check(
                            userId: (int) (filament()->auth()->id() ?? 0),
                            operationType: 'delete_beauty_salon',
                            amount: 0,
                            correlationId: $correlationId,
                        );

                        $logger->info('BeautySalon deleted via Filament', [
                            'salon_id'       => $record->id,
                            'tenant_id'      => filament()->getTenant()?->id,
                            'correlation_id' => $correlationId,
                        ]);

                        $record->delete();
                    });
                }),
        ];
    }
}
