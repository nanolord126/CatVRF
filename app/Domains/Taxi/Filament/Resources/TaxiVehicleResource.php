<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxiVehicleResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = TaxiVehicle::class;

        protected static ?string $navigationLabel = 'Автомобили';

        protected static ?string $pluralModelLabel = 'Автомобили';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация об авто')
                    ->schema([
                        Forms\Components\TextInput::make('brand')
                            ->label('Марка')
                            ->required(),

                        Forms\Components\TextInput::make('model')
                            ->label('Модель')
                            ->required(),

                        Forms\Components\TextInput::make('license_plate')
                            ->label('Гос. номер')
                            ->required()
                            ->unique(TaxiVehicle::class, 'license_plate', ignoreRecord: true),

                        Forms\Components\TextInput::make('year')
                            ->label('Год выпуска')
                            ->numeric()
                            ->required(),

                        Forms\Components\Select::make('class')
                            ->label('Класс')
                            ->options([
                                'economy' => 'Эконом',
                                'comfort' => 'Комфорт',
                                'business' => 'Бизнес',
                            ])
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активно',
                                'maintenance' => 'На обслуживании',
                                'inactive' => 'Неактивно',
                            ])
                            ->required(),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('brand')
                        ->label('Марка')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('model')
                        ->label('Модель')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('license_plate')
                        ->label('Гос. номер')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('class')
                        ->label('Класс')
                        ->badge(),

                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->badge(),

                    Tables\Columns\TextColumn::make('year')
                        ->label('Год')
                        ->numeric(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('class')
                        ->options([
                            'economy' => 'Эконом',
                            'comfort' => 'Комфорт',
                            'business' => 'Бизнес',
                        ]),

                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                            'active' => 'Активно',
                            'maintenance' => 'На обслуживании',
                            'inactive' => 'Неактивно',
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
                'index' => Pages\ListTaxiVehicles::route('/'),
                'create' => Pages\CreateTaxiVehicle::route('/create'),
                'edit' => Pages\EditTaxiVehicle::route('/{record}/edit'),
                'view' => Pages\ViewTaxiVehicle::route('/{record}'),
            ];
        }
}
