<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources;

use Filament\Resources\Resource;

final class CarDetailingResource extends Resource
{

    protected static ?string $model = CarDetailing::class;

        protected static ?string $navigationLabel = 'Детейлинг';

        protected static ?string $pluralModelLabel = 'Детейлинг';

        protected static ?string $navigationGroup = 'Авто';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация о детейлинге')
                    ->schema([
                        Forms\Components\Select::make('vehicle_id')
                            ->label('Автомобиль')
                            ->relationship('vehicle', 'license_plate')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('client_id')
                            ->label('Клиент')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->label('Тип')
                            ->options([
                                'exterior' => 'Экстерьер',
                                'interior' => 'Интерьер',
                                'full' => 'Полный',
                                'ceramic_coating' => 'Керамическое покрытие',
                                'paint_correction' => 'Полировка ЛКП',
                            ])
                            ->required(),

                        Forms\Components\DateTimePicker::make('datetime_start')
                            ->label('Дата и время начала')
                            ->required(),

                        Forms\Components\TextInput::make('duration_hours')
                            ->label('Длительность (часы)')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('price')
                            ->label('Цена (копейки)')
                            ->numeric()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидает',
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
                    Tables\Columns\TextColumn::make('vehicle.license_plate')
                        ->label('Автомобиль')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('client.name')
                        ->label('Клиент')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('type')
                        ->label('Тип')
                        ->badge(),

                    Tables\Columns\TextColumn::make('datetime_start')
                        ->label('Дата и время')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('price')
                        ->label('Цена')
                        ->money('RUB', divideBy: 100)
                        ->sortable(),

                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'in_progress' => 'info',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'pending' => 'Ожидает',
                            'in_progress' => 'В процессе',
                            'completed' => 'Завершено',
                            'cancelled' => 'Отменено',
                        ]),

                    Tables\Filters\SelectFilter::make('type')
                        ->label('Тип')
                        ->options([
                            'exterior' => 'Экстерьер',
                            'interior' => 'Интерьер',
                            'full' => 'Полный',
                            'ceramic_coating' => 'Керамическое покрытие',
                            'paint_correction' => 'Полировка ЛКП',
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
                'index' => Pages\ListCarDetailings::route('/'),
                'create' => Pages\CreateCarDetailing::route('/create'),
                'edit' => Pages\EditCarDetailing::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
