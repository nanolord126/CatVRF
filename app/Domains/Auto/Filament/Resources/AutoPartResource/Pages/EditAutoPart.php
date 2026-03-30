<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\AutoPartResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditAutoPart extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = AutoPartResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\Action::make('restock')
                    ->label('Пополнить остаток')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Количество для пополнения')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\Textarea::make('reason')
                            ->label('Причина пополнения')
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        DB::transaction(function () use ($data) {
                            $oldStock = $this->record->current_stock;
                            $this->record->current_stock += (int) $data['quantity'];
                            $this->record->save();

                            event(new AutoPartStockUpdated(
                                $this->record,
                                $oldStock,
                                $this->record->current_stock,
                                $this->record->correlation_id
                            ));

                            Log::channel('audit')->info('Auto part stock updated (restock)', [
                                'correlation_id' => $this->record->correlation_id,
                                'part_id' => $this->record->id,
                                'old_stock' => $oldStock,
                                'new_stock' => $this->record->current_stock,
                                'quantity' => $data['quantity'],
                                'reason' => $data['reason'] ?? null,
                                'user_id' => auth()->id(),
                            ]);

                            $this->notification->make()
                                ->title('Остаток пополнен')
                                ->body("SKU: {$this->record->sku}, новый остаток: {$this->record->current_stock} шт")
                                ->success()
                                ->send();
                        });
                    }),

                Actions\DeleteAction::make()
                    ->after(function () {
                        Log::channel('audit')->info('Auto part deleted', [
                            'correlation_id' => $this->record->correlation_id,
                            'part_id' => $this->record->id,
                            'sku' => $this->record->sku,
                            'user_id' => auth()->id(),
                        ]);
                    }),
            ];
        }

        protected function afterSave(): void
        {
            Log::channel('audit')->info('Auto part updated', [
                'correlation_id' => $this->record->correlation_id,
                'part_id' => $this->record->id,
                'sku' => $this->record->sku,
                'current_stock' => $this->record->current_stock,
                'user_id' => auth()->id(),
            ]);
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
