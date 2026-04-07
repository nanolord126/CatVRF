<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\ServiceWarrantyResource\Pages;


use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;

final class EditServiceWarranty extends EditRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    protected static string $resource = ServiceWarrantyResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make()
                    ->after(function () {
                        $this->logger->info('ServiceWarranty deleted', [
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

                            $this->logger->info('ServiceWarrantyClaimApproved', [
                                'correlation_id' => $this->record->correlation_id,
                                'warranty_id' => $this->record->id,
                            ]);

                            event(new ServiceWarrantyClaimApproved(
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
                        \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Причина отклонения')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $this->db->transaction(function () use ($data) {
                            $this->record->update(['claim_status' => 'rejected']);

                            $this->logger->info('ServiceWarrantyClaimRejected', [
                                'correlation_id' => $this->record->correlation_id,
                                'warranty_id' => $this->record->id,
                                'reason' => $data['rejection_reason'],
                            ]);

                            event(new ServiceWarrantyClaimRejected(
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

            $this->logger->info('ServiceWarranty updated', [
                'correlation_id' => $this->record->correlation_id,
                'warranty_id' => $this->record->id,
                'claim_status' => $this->record->claim_status,
            ]);

            if ($wasChanged && $this->record->claim_status === 'pending') {
                event(new ServiceWarrantyClaimSubmitted(
                    $this->record,
                    $this->record->correlation_id
                ));
            }
        }
}
