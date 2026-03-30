<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SaleListingResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = SaleListing::class;

        protected static ?string $navigationIcon = 'heroicon-o-tag';

        protected static ?string $navigationGroup = 'Real Estate';

        protected static ?string $label = 'Продажа';

        protected static ?string $pluralLabel = 'Объявления о продаже';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Информация о продаже')
                        ->schema([
                            TextInput::make('sale_price')
                                ->label('Цена (₽)')
                                ->numeric()
                                ->required(),
                            TextInput::make('commission_percent')
                                ->label('Комиссия (%)')
                                ->numeric()
                                ->default(14)
                                ->required(),
                            ToggleButtons::make('auction')
                                ->label('Аукцион')
                                ->boolean(),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('property.address')
                        ->label('Адрес')
                        ->searchable(),
                    TextColumn::make('sale_price')
                        ->label('Цена')
                        ->money('RUB', 100)
                        ->sortable(),
                    TextColumn::make('commission_percent')
                        ->label('Комиссия')
                        ->suffix('%'),
                    BadgeColumn::make('status')
                        ->label('Статус')
                        ->colors([
                            'success' => 'active',
                            'danger' => 'sold',
                            'secondary' => 'archived',
                        ]),
                    TextColumn::make('created_at')
                        ->label('Создано')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'active' => 'Активно',
                            'sold' => 'Продано',
                            'archived' => 'Архив',
                        ]),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\RealEstate\Filament\Resources\SaleListingResource\Pages\ListSaleListings::route('/'),
            ];
        }
}
