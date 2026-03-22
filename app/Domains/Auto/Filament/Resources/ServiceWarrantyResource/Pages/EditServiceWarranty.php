<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\ServiceWarrantyResource\Pages;

use App\Domains\Auto\Filament\Resources\ServiceWarrantyResource;
use App\Domains\Auto\Events\ServiceWarrantyClaimSubmitted;
use App\Domains\Auto\Events\ServiceWarrantyClaimApproved;
use App\Domains\Auto\Events\ServiceWarrantyClaimRejected;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class EditServiceWarranty extends EditRecord
{
    protected static string $resource = ServiceWarrantyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    Log::channel('audit')->info('ServiceWarranty deleted', [
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
                    DB::transaction(function () {
                        $this->record->update(['claim_status' => 'approved']);
                        
                        Log::channel('audit')->info('ServiceWarrantyClaimApproved', [
                            'correlation_id' => $this->record->correlation_id,
                            'warranty_id' => $this->record->id,
                        ]);

                        event(new ServiceWarrantyClaimApproved(
                            $this->record,
                            $this->record->correlation_id
                        ));
                    });

                    Notification::make()
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
                    DB::transaction(function () use ($data) {
                        $this->record->update(['claim_status' => 'rejected']);
                        
                        Log::channel('audit')->info('ServiceWarrantyClaimRejected', [
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

                    Notification::make()
                        ->warning()
                        ->title('Претензия отклонена')
                        ->send();
                }),
        ];
    }

    protected function afterSave(): void
    {
        $wasChanged = $this->record->wasChanged('claim_status');
        
        Log::channel('audit')->info('ServiceWarranty updated', [
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
