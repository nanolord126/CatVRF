<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources;

use App\Domains\Taxi\Filament\Resources\TaxiDriverResource\Pages;
use App\Domains\Taxi\Models\TaxiDriver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class TaxiDriverResource extends Resource
{
    protected static ?string $model = TaxiDriver::class;

        protected static ?string $navigationLabel = 'Водители такси';

        protected static ?string $pluralModelLabel = 'Водители такси';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->label('User ID')
                            ->required()
                            ->numeric(),

                        Forms\Components\TextInput::make('license_number')
                            ->label('Номер лицензии')
                            ->required()
                            ->unique(TaxiDriver::class, 'license_number', ignoreRecord: true),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активный')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Статистика')
                    ->schema([
                        Forms\Components\TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->step(0.1)
                            ->disabled(),

                        Forms\Components\TextInput::make('completed_rides')
                            ->label('Завершённых поездок')
                            ->numeric()
                            ->disabled(),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('user_id')
                        ->label('User ID')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('license_number')
                        ->label('Лицензия')
                        ->searchable(),

                    Tables\Columns\IconColumn::make('is_active')
                        ->label('Активен')
                        ->boolean(),

                    Tables\Columns\TextColumn::make('rating')
                        ->label('Рейтинг')
                        ->numeric()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('completed_rides')
                        ->label('Поездок')
                        ->numeric()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Создан')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\TernaryFilter::make('is_active')
                        ->label('Статус'),
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

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\Taxi\Filament\Resources\TaxiDriverResource\Pages\ListTaxiDrivers::route('/'),
                'create' => \App\Domains\Taxi\Filament\Resources\TaxiDriverResource\Pages\CreateTaxiDriver::route('/create'),
                'edit' => \App\Domains\Taxi\Filament\Resources\TaxiDriverResource\Pages\EditTaxiDriver::route('/{record}/edit'),
                'view' => \App\Domains\Taxi\Filament\Resources\TaxiDriverResource\Pages\ViewTaxiDriver::route('/{record}'),
            ];
        }
}
