<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources;

use Filament\Resources\Resource;

final class TowingRequestResource extends Resource
{

    protected static ?string $model = TowingRequest::class;

        protected static ?string $navigationLabel = 'Эвакуатор';

        protected static ?string $pluralModelLabel = 'Заявки на эвакуатор';

        protected static ?string $navigationGroup = 'Авто';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация о заявке')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Клиент')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('pickup_location')
                            ->label('Адрес подачи')
                            ->required(),

                        Forms\Components\TextInput::make('dropoff_location')
                            ->label('Адрес доставки')
                            ->required(),

                        Forms\Components\TextInput::make('vehicle_type')
                            ->label('Тип транспорта')
                            ->required(),

                        Forms\Components\TextInput::make('price')
                            ->label('Стоимость (копейки)')
                            ->numeric()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидает',
                                'assigned' => 'Назначен водитель',
                                'in_progress' => 'В процессе',
                                'completed' => 'Завершено',
                                'cancelled' => 'Отменено',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Примечания')
                            ->columnSpanFull(),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('client.name')
                        ->label('Клиент')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('pickup_location')
                        ->label('Откуда')
                        ->limit(30)
                        ->searchable(),

                    Tables\Columns\TextColumn::make('dropoff_location')
                        ->label('Куда')
                        ->limit(30)
                        ->searchable(),

                    Tables\Columns\TextColumn::make('price')
                        ->label('Стоимость')
                        ->money('RUB', divideBy: 100)
                        ->sortable(),

                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'assigned' => 'info',
                            'in_progress' => 'primary',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }),

                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Создано')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'pending' => 'Ожидает',
                            'assigned' => 'Назначен водитель',
                            'in_progress' => 'В процессе',
                            'completed' => 'Завершено',
                            'cancelled' => 'Отменено',
                        ]),
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
                'index' => Pages\ListTowingRequests::route('/'),
                'create' => Pages\CreateTowingRequest::route('/create'),
                'edit' => Pages\EditTowingRequest::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
