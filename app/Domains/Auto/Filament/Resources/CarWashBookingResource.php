<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarWashBookingResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = CarWashBooking::class;

        protected static ?string $navigationLabel = 'Бронь мойки';

        protected static ?string $pluralModelLabel = 'Брони мойки';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация о брони')
                    ->schema([
                        Forms\Components\TextInput::make('client_id')
                            ->label('Клиент')
                            ->required()
                            ->numeric(),

                        Forms\Components\TextInput::make('wash_type')
                            ->label('Тип мойки')
                            ->required(),

                        Forms\Components\TextInput::make('box_number')
                            ->label('Номер бокса')
                            ->numeric(),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Запланирована на')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'В ожидании',
                                'in_progress' => 'В процессе',
                                'completed' => 'Завершена',
                                'cancelled' => 'Отменена',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('price')
                            ->label('Цена (копейки)')
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
                        ->searchable(),

                    Tables\Columns\TextColumn::make('wash_type')
                        ->label('Тип мойки'),

                    Tables\Columns\TextColumn::make('box_number')
                        ->label('Бокс'),

                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->badge(),

                    Tables\Columns\TextColumn::make('scheduled_at')
                        ->label('Дата')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('price')
                        ->label('Цена')
                        ->formatStateUsing(fn ($state) => ($state / 100) . ' ₽'),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                            'pending' => 'В ожидании',
                            'in_progress' => 'В процессе',
                            'completed' => 'Завершена',
                            'cancelled' => 'Отменена',
                        ]),

                    Tables\Filters\SelectFilter::make('wash_type'),
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
                'index' => Pages\ListCarWashBookings::route('/'),
                'create' => Pages\CreateCarWashBooking::route('/create'),
                'edit' => Pages\EditCarWashBooking::route('/{record}/edit'),
                'view' => Pages\ViewCarWashBooking::route('/{record}'),
            ];
        }
}
