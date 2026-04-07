<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources;

use Filament\Resources\Resource;

final class AutoServiceOrderResource extends Resource
{

    protected static ?string $model = AutoServiceOrder::class;

        protected static ?string $navigationLabel = 'Заказы СТО';

        protected static ?string $pluralModelLabel = 'Заказы СТО';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация о заказе')
                    ->schema([
                        Forms\Components\TextInput::make('client_id')
                            ->label('Клиент')
                            ->required()
                            ->numeric(),

                        Forms\Components\TextInput::make('car_brand')
                            ->label('Марка авто')
                            ->required(),

                        Forms\Components\TextInput::make('car_model')
                            ->label('Модель авто')
                            ->required(),

                        Forms\Components\Select::make('service_id')
                            ->label('Услуга')
                            ->relationship('service', 'name'),

                        Forms\Components\DateTimePicker::make('appointment_datetime')
                            ->label('Дата и время')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'В ожидании',
                                'in_progress' => 'В процессе',
                                'completed' => 'Завершён',
                                'cancelled' => 'Отменён',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('total_price')
                            ->label('Сумма (копейки)')
                            ->numeric()
                            ->required(),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('client_id')
                        ->label('Клиент')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('car_brand')
                        ->label('Марка'),

                    Tables\Columns\TextColumn::make('car_model')
                        ->label('Модель'),

                    Tables\Columns\TextColumn::make('service.name')
                        ->label('Услуга'),

                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->badge(),

                    Tables\Columns\TextColumn::make('appointment_datetime')
                        ->label('Дата')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('total_price')
                        ->label('Сумма')
                        ->formatStateUsing(fn ($state) => ($state / 100) . ' ₽'),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                            'pending' => 'В ожидании',
                            'in_progress' => 'В процессе',
                            'completed' => 'Завершён',
                            'cancelled' => 'Отменён',
                        ]),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
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
                'index' => Pages\ListAutoServiceOrders::route('/'),
                'create' => Pages\CreateAutoServiceOrder::route('/create'),
                'edit' => Pages\EditAutoServiceOrder::route('/{record}/edit'),
                'view' => Pages\ViewAutoServiceOrder::route('/{record}'),
            ];
        }
}
