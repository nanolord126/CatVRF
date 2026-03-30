<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TuningProjectResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = TuningProject::class;

        protected static ?string $navigationLabel = 'Тюнинг';

        protected static ?string $pluralModelLabel = 'Проекты тюнинга';

        protected static ?string $navigationGroup = 'Авто';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация о проекте')
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
                            ->label('Тип тюнинга')
                            ->options([
                                'engine' => 'Двигатель',
                                'suspension' => 'Подвеска',
                                'exterior' => 'Экстерьер',
                                'interior' => 'Интерьер',
                                'electronics' => 'Электроника',
                                'exhaust' => 'Выхлопная система',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('estimated_price')
                            ->label('Предварительная стоимость (копейки)')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('final_price')
                            ->label('Итоговая стоимость (копейки)')
                            ->numeric(),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Дата начала')
                            ->required(),

                        Forms\Components\DatePicker::make('completion_date')
                            ->label('Дата завершения'),

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

                        Forms\Components\Textarea::make('description')
                            ->label('Описание работ')
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

                    Tables\Columns\TextColumn::make('start_date')
                        ->label('Начало')
                        ->date('d.m.Y')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('estimated_price')
                        ->label('Стоимость')
                        ->money('RUB', divideBy: 100)
                        ->sortable(),

                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending' => 'warning',
                            'in_progress' => 'info',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('type')
                        ->label('Тип')
                        ->options([
                            'engine' => 'Двигатель',
                            'suspension' => 'Подвеска',
                            'exterior' => 'Экстерьер',
                            'interior' => 'Интерьер',
                            'electronics' => 'Электроника',
                            'exhaust' => 'Выхлопная система',
                        ]),

                    Tables\Filters\SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'pending' => 'Ожидает',
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
                'index' => Pages\ListTuningProjects::route('/'),
                'create' => Pages\CreateTuningProject::route('/create'),
                'edit' => Pages\EditTuningProject::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
