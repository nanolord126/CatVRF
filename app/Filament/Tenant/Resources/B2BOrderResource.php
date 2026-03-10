<?php

namespace App\Filament\Tenant\Resources;

use App\Models\B2BOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use App\Filament\Tenant\Resources\B2BOrderResource\Pages;
use App\Services\Common\Support\HelpdeskService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class B2BOrderResource extends Resource
{
    protected static ?string $model = B2BOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'B2B/Corporate';
    protected static ?string $modelLabel = 'B2B Order';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Select::make('partner_id')
                    ->relationship('partner', 'name')
                    ->required(),
                Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->prefix('₽'),
                Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'invoiced' => 'Invoiced',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Components\Placeholder::make('origin_info')
                    ->label('Source')
                    ->content(fn ($record) => $record ? "{$record->origin_type} #{$record->origin_id}" : '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('id')->label('Order ID'),
                Columns\TextColumn::make('partner.name')->searchable(),
                Columns\TextColumn::make('amount')->money('RUB')->sortable(),
                Columns\BadgeColumn::make('status'),
                Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // Действие для связи с поддержкой по заказу
                Action::make('support')
                    ->label('Platform Support')
                    ->icon('heroicon-o-lifebuoy')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Открыть спор по заказу?')
                    ->modalDescription('Администрация платформы рассмотрит вашу жалобу.')
                    ->form([
                        Forms\Components\Textarea::make('message')->required(),
                    ])
                    ->action(function ($record, $data, HelpdeskService $helpdesk) {
                        $helpdesk->openTicket(tenant(), auth()->id(), [
                            'subject' => "B2B Order Dispute: #{$record->id}",
                            'category' => 'fraud_dispute',
                            'priority' => 'high'
                        ]);
                        
                        Notification::make()->title('Тикет создан')->success()->send();
                    }),

                // Прямой чат с контрагентом
                Action::make('chat')
                    ->label('Chat with Partner')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn ($record) => route('filament.tenant.support.chat', ['chatId' => 1])), // Пример маршрута чата
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageB2BOrders::route('/'),
        ];
    }
}
