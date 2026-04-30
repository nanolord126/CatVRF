<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Restaurant\Infrastructure\Models\OrderKitchenStatusModel;
use Modules\Restaurant\Domain\Enums\OrderKitchenStatusEnum;
use Modules\Restaurant\Domain\Enums\OrderPriority;

final class OrderKitchenStatusResource extends Resource
{
    protected static ?string $model = OrderKitchenStatusModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Kitchen Orders';

    protected static ?string $modelLabel = 'Kitchen Order';

    protected static ?string $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'id')
                            ->searchable()
                            ->required()
                            ->label('Order'),

                        Forms\Components\Select::make('kitchen_station_id')
                            ->relationship('kitchenStation', 'name')
                            ->searchable()
                            ->required()
                            ->label('Kitchen Station'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => OrderKitchenStatusEnum::PENDING->label(),
                                'in_progress' => OrderKitchenStatusEnum::IN_PROGRESS->label(),
                                'ready' => OrderKitchenStatusEnum::READY->label(),
                                'served' => OrderKitchenStatusEnum::SERVED->label(),
                                'cancelled' => OrderKitchenStatusEnum::CANCELLED->label(),
                                'problem' => OrderKitchenStatusEnum::PROBLEM->label(),
                            ])
                            ->required()
                            ->label('Status'),

                        Forms\Components\Select::make('priority')
                            ->options([
                                'normal' => OrderPriority::NORMAL->label(),
                                'high' => OrderPriority::HIGH->label(),
                                'urgent' => OrderPriority::URGENT->label(),
                                'vip' => OrderPriority::VIP->label(),
                                'emergency' => OrderPriority::EMERGENCY->label(),
                            ])
                            ->required()
                            ->label('Priority'),

                        Forms\Components\TextInput::make('estimated_preparation_minutes')
                            ->numeric()
                            ->default(15)
                            ->required()
                            ->label('Est. Preparation (min)'),

                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Started At'),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completed At'),

                        Forms\Components\Textarea::make('problem_comment')
                            ->rows(3)
                            ->label('Problem Comment'),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->label('Notes'),

                        Forms\Components\Toggle::make('is_from_marketplace')
                            ->label('From Marketplace'),

                        Forms\Components\Toggle::make('is_vip')
                            ->label('VIP'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.id')
                    ->searchable()
                    ->sortable()
                    ->label('Order #'),

                Tables\Columns\TextColumn::make('kitchenStation.name')
                    ->searchable()
                    ->sortable()
                    ->label('Station'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'blue',
                        'ready' => 'green',
                        'served' => 'emerald',
                        'cancelled' => 'red',
                        'problem' => 'orange',
                    })
                    ->formatStateUsing(fn (string $state): string => OrderKitchenStatusEnum::from($state)->label())
                    ->label('Status'),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'normal' => 'gray',
                        'high' => 'blue',
                        'urgent' => 'orange',
                        'vip' => 'purple',
                        'emergency' => 'red',
                    })
                    ->formatStateUsing(fn (string $state): string => OrderPriority::from($state)->label())
                    ->label('Priority'),

                Tables\Columns\TextColumn::make('estimated_preparation_minutes')
                    ->sortable()
                    ->label('Est. Time (min)'),

                Tables\Columns\IconColumn::make('is_from_marketplace')
                    ->boolean()
                    ->label('Marketplace'),

                Tables\Columns\IconColumn::make('is_vip')
                    ->boolean()
                    ->label('VIP'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('kitchen_station_id')
                    ->relationship('kitchenStation', 'name')
                    ->label('Station'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => OrderKitchenStatusEnum::PENDING->label(),
                        'in_progress' => OrderKitchenStatusEnum::IN_PROGRESS->label(),
                        'ready' => OrderKitchenStatusEnum::READY->label(),
                        'served' => OrderKitchenStatusEnum::SERVED->label(),
                        'cancelled' => OrderKitchenStatusEnum::CANCELLED->label(),
                        'problem' => OrderKitchenStatusEnum::PROBLEM->label(),
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'normal' => OrderPriority::NORMAL->label(),
                        'high' => OrderPriority::HIGH->label(),
                        'urgent' => OrderPriority::URGENT->label(),
                        'vip' => OrderPriority::VIP->label(),
                        'emergency' => OrderPriority::EMERGENCY->label(),
                    ]),

                Tables\Filters\TernaryFilter::make('is_from_marketplace')
                    ->label('From Marketplace'),

                Tables\Filters\TernaryFilter::make('is_vip')
                    ->label('VIP'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Restaurant\Presentation\Resources\OrderKitchenStatusResource\Pages\ListOrderKitchenStatuses::route('/'),
            'create' => \Modules\Restaurant\Presentation\Resources\OrderKitchenStatusResource\Pages\CreateOrderKitchenStatus::route('/create'),
            'edit' => \Modules\Restaurant\Presentation\Resources\OrderKitchenStatusResource\Pages\EditOrderKitchenStatus::route('/{record}/edit'),
        ];
    }
}
