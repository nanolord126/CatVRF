<?php

declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\PartWarrantyResource\Pages;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\DatabaseManager;

final class EditPartWarranty extends EditRecord
{
    protected static string $resource = PartWarrantyResource::class;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger) {}

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    $this->logger->$this->logger->info('PartWarranty deleted', [
                        'correlation_id' => $this->record->correlation_id,
                        'warranty_id' => $this->record->id,
                    ]);
                }),
            Actions\Action::make('approve_claim')
                ->label('Одобрить претензию')
                ->color('success')
                ->visible(fn () => $this->record->claim_status === 'pending')
                ->requiresConfirmation()
                ->action(function () {
                    $this->db->transaction(function () {
                        $this->record->update(['claim_status' => 'approved']);

                        $this->logger->$this->logger->info('PartWarrantyClaimApproved', [
                            'correlation_id' => $this->record->correlation_id,
                            'warranty_id' => $this->record->id,
                        ]);

                        $this->eventDispatcher->dispatch(new PartWarrantyClaimApproved(
                            $this->record,
                            $this->record->correlation_id
                        ));
                    });

                    $this->notification->make()
                        ->success()
                        ->title('Претензия одобрена')
                        ->send();
                }),
            Actions\Action::make('reject_claim')
                ->label('Отклонить претензию')
                ->color('danger')
                ->visible(fn () => $this->record->claim_status === 'pending')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Причина отклонения')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->db->transaction(function () use ($data) {
                        $this->record->update(['claim_status' => 'rejected']);

                        $this->logger->$this->logger->info('PartWarrantyClaimRejected', [
                            'correlation_id' => $this->record->correlation_id,
                            'warranty_id' => $this->record->id,
                            'reason' => $data['rejection_reason'],
                        ]);

                        $this->eventDispatcher->dispatch(new PartWarrantyClaimRejected(
                            $this->record,
                            $data['rejection_reason'],
                            $this->record->correlation_id
                        ));
                    });

                    $this->notification->make()
                        ->warning()
                        ->title('Претензия отклонена')
                        ->send();
                }),
        ];
    }

    protected function afterSave(): void
    {
        $wasChanged = $this->record->wasChanged('claim_status');

        $this->logger->$this->logger->info('PartWarranty updated', [
            'correlation_id' => $this->record->correlation_id,
            'warranty_id' => $this->record->id,
            'claim_status' => $this->record->claim_status,
        ]);

        if ($wasChanged && $this->record->claim_status === 'pending') {
            $this->eventDispatcher->dispatch(new PartWarrantyClaimSubmitted(
                $this->record,
                $this->record->correlation_id
            ));
        }
    }
}
