<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\PartWarrantyResource\Pages;

use App\Domains\Auto\Filament\Resources\PartWarrantyResource;
use App\Domains\Auto\Events\PartWarrantyClaimSubmitted;
use App\Domains\Auto\Events\PartWarrantyClaimApproved;
use App\Domains\Auto\Events\PartWarrantyClaimRejected;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class EditPartWarranty extends EditRecord
{
    protected static string $resource = PartWarrantyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    $this->log->channel('audit')->info('PartWarranty deleted', [
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
                        
                        $this->log->channel('audit')->info('PartWarrantyClaimApproved', [
                            'correlation_id' => $this->record->correlation_id,
                            'warranty_id' => $this->record->id,
                        ]);

                        event(new PartWarrantyClaimApproved(
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
                        
                        $this->log->channel('audit')->info('PartWarrantyClaimRejected', [
                            'correlation_id' => $this->record->correlation_id,
                            'warranty_id' => $this->record->id,
                            'reason' => $data['rejection_reason'],
                        ]);

                        event(new PartWarrantyClaimRejected(
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
        
        $this->log->channel('audit')->info('PartWarranty updated', [
            'correlation_id' => $this->record->correlation_id,
            'warranty_id' => $this->record->id,
            'claim_status' => $this->record->claim_status,
        ]);

        if ($wasChanged && $this->record->claim_status === 'pending') {
            event(new PartWarrantyClaimSubmitted(
                $this->record,
                $this->record->correlation_id
            ));
        }
    }
}
