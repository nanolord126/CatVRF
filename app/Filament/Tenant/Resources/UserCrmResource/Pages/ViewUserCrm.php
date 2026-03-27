<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\UserCrmResource\Pages;

use App\Filament\Tenant\Resources\UserCrmResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

final class ViewUserCrm extends ViewRecord
{
    protected static string $resource = UserCrmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_notification')
                ->label('Отправить уведомление')
                ->icon('heroicon-o-bell')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Textarea::make('message')
                        ->label('Сообщение')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    $correlationId = (string) \Illuminate\Support\Str::uuid()->toString();
                    \Illuminate\Support\Facades\Log::channel('audit')->info('CRM: Send notification', [
                        'user_id' => $this->record->id,
                        'tenant_id' => filament()->getTenant()?->id,
                        'correlation_id' => $correlationId,
                    ]);
                    // Notification::send($this->record, new CrmMessageNotification($data['message']));
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Уведомление отправлено')
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Профиль клиента')->schema([
                Infolists\Components\Grid::make(3)->schema([
                    Infolists\Components\TextEntry::make('name')
                        ->label('Имя'),

                    Infolists\Components\TextEntry::make('email')
                        ->label('Email')
                        ->copyable(),

                    Infolists\Components\TextEntry::make('phone')
                        ->label('Телефон')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Дата регистрации')
                        ->dateTime('d.m.Y H:i'),

                    Infolists\Components\TextEntry::make('payment_transactions_count')
                        ->label('Заказов')
                        ->badge()
                        ->color('info'),

                    Infolists\Components\TextEntry::make('payment_transactions_sum_amount')
                        ->label('Всего потрачено')
                        ->formatStateUsing(fn($state) => $state
                            ? number_format($state / 100, 2, '.', ' ') . ' ₽'
                            : '0.00 ₽'
                        )
                        ->badge()
                        ->color('success'),
                ]),
            ]),

            Infolists\Components\Section::make('Кошелёк')->schema([
                Infolists\Components\Grid::make(2)->schema([
                    Infolists\Components\TextEntry::make('wallet_balance')
                        ->label('Текущий баланс')
                        ->formatStateUsing(fn($state) => $state !== null
                            ? number_format((int)$state / 100, 2, '.', ' ') . ' ₽'
                            : '0.00 ₽'
                        )
                        ->badge()
                        ->color(fn($state) => ($state ?? 0) > 0 ? 'success' : 'gray'),

                    Infolists\Components\TextEntry::make('last_order_at')
                        ->label('Последний заказ')
                        ->dateTime('d.m.Y H:i')
                        ->placeholder('—'),
                ]),
            ]),
        ]);
    }
}
