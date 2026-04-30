<?php

declare(strict_types=1);

namespace Modules\Flowers\Filament\Resources;

use Modules\Flowers\Infrastructure\Models\OrderModel;
use Modules\Flowers\Domain\Enums\OrderStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

final class OrderResource extends Resource
{
    protected static ?string $model = OrderModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Flowers CRM';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'in_assembly' => 'In Assembly',
                                'assembled' => 'Assembled',
                                'quality_checked' => 'Quality Checked',
                                'ready_for_delivery' => 'Ready for Delivery',
                                'out_for_delivery' => 'Out for Delivery',
                                'delivered' => 'Delivered',
                                'picked_up' => 'Picked Up',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'first_name')
                            ->searchable()
                            ->required()
                            ->preload(),
                        Forms\Components\Select::make('florist_id')
                            ->relationship('florist', 'first_name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Delivery Information')
                    ->schema([
                        Forms\Components\Select::make('delivery_type')
                            ->options([
                                'delivery' => 'Delivery',
                                'pickup' => 'Pickup',
                            ])
                            ->required(),
                        Forms\Components\Select::make('delivery_slot_id')
                            ->relationship('deliverySlot', 'start_time')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('delivery_date'),
                        Forms\Components\TextInput::make('recipient_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('recipient_phone')
                            ->required()
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('delivery_address')
                            ->rows(2),
                        Forms\Components\Textarea::make('delivery_instructions')
                            ->rows(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Financial Information')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->numeric()
                            ->prefix('₽')
                            ->disabled(),
                        Forms\Components\TextInput::make('delivery_fee')
                            ->numeric()
                            ->prefix('₽')
                            ->disabled(),
                        Forms\Components\TextInput::make('discount_amount')
                            ->numeric()
                            ->prefix('₽')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('₽')
                            ->disabled(),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('card_message')
                            ->rows(3),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                        Forms\Components\Toggle::make('is_urgent'),
                        Forms\Components\Toggle::make('is_corporate'),
                        Forms\Components\TextInput::make('source')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.first_name')
                    ->label('Client')
                    ->searchable(['client.first_name', 'client.last_name']),
                ViewColumn::make('status')
                    ->label('Статус')
                    ->view('components.status-badge')
                    ->viewData(fn ($record): array => [
                        'status' => $record->status,
                        'label' => match ($record->status) {
                            'pending' => 'Ожидает',
                            'confirmed' => 'Подтверждён',
                            'in_assembly' => 'В сборке',
                            'assembled' => 'Собран',
                            'quality_checked' => 'Проверен',
                            'ready_for_delivery' => 'Готов к доставке',
                            'out_for_delivery' => 'В доставке',
                            'delivered' => 'Доставлен',
                            'picked_up' => 'Самовывоз',
                            'cancelled' => 'Отменён',
                            'refunded' => 'Возврат',
                            default => ucfirst($record->status),
                        },
                        'color' => match ($record->status) {
                            'pending' => 'secondary',
                            'confirmed' => 'info',
                            'in_assembly' => 'warning',
                            'assembled' => 'success',
                            'quality_checked' => 'info',
                            'ready_for_delivery' => 'primary',
                            'out_for_delivery' => 'cyan',
                            'delivered', 'picked_up' => 'success',
                            'cancelled', 'refunded' => 'danger',
                            default => 'secondary',
                        },
                        'icon' => match ($record->status) {
                            'pending' => 'clock',
                            'confirmed' => 'check-circle',
                            'in_assembly' => 'scissors',
                            'assembled' => 'check-badge',
                            'quality_checked' => 'shield-check',
                            'ready_for_delivery' => 'truck',
                            'out_for_delivery' => 'paper-airplane',
                            'delivered' => 'check-circle',
                            'picked_up' => 'shopping-bag',
                            'cancelled' => 'x-circle',
                            'refunded' => 'arrow-uturn-left',
                            default => null,
                        },
                    ]),
                TextColumn::make('total_amount')
                    ->money('RUB')
                    ->sortable(),
                TextColumn::make('delivery_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('florist.first_name')
                    ->label('Florist')
                    ->searchable(['florist.first_name', 'florist.last_name']),
                BadgeColumn::make('payment_status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'failed',
                        'gray' => 'refunded',
                    ]),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'in_assembly' => 'In Assembly',
                        'assembled' => 'Assembled',
                        'quality_checked' => 'Quality Checked',
                        'ready_for_delivery' => 'Ready for Delivery',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                        'picked_up' => 'Picked Up',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('florist')
                    ->relationship('florist', 'first_name'),
                Filter::make('is_urgent')
                    ->query(fn (Builder $query): Builder => $query->where('is_urgent', true)),
                Filter::make('is_corporate')
                    ->query(fn (Builder $query): Builder => $query->where('is_corporate', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'items' => Tables\Relations\RelationManager::class,
            'modifiers' => Tables\Relations\RelationManager::class,
            'photos' => Tables\Relations\RelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Flowers\Filament\Resources\OrderResource\Pages\ListOrders::route('/'),
            'create' => \Modules\Flowers\Filament\Resources\OrderResource\Pages\CreateOrder::route('/create'),
            'view' => \Modules\Flowers\Filament\Resources\OrderResource\Pages\ViewOrder::route('/{record}'),
            'edit' => \Modules\Flowers\Filament\Resources\OrderResource\Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
