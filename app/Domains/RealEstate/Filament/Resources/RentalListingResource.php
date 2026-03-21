<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Filament\Resources;

use App\Domains\RealEstate\Models\RentalListing;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

/**
 * Filament Resource для управления объявлениями об аренде.
 * Production 2026.
 */
final class RentalListingResource extends Resource
{
    protected static ?string $model = RentalListing::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Real Estate';

    protected static ?string $label = 'Аренда';

    protected static ?string $pluralLabel = 'Объявления об аренде';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Информация об аренде')
                    ->schema([
                        TextInput::make('rent_price_month')
                            ->label('Месячная аренда (₽)')
                            ->numeric()
                            ->required(),
                        TextInput::make('deposit')
                            ->label('Залог (₽)')
                            ->numeric(),
                        TextInput::make('lease_term_min')
                            ->label('Минимальный срок (месяцы)')
                            ->numeric()
                            ->required(),
                        TextInput::make('lease_term_max')
                            ->label('Максимальный срок (месяцы)')
                            ->numeric(),
                        ToggleButtons::make('is_furnished')
                            ->label('Меблирована')
                            ->boolean(),
                        ToggleButtons::make('pets_allowed')
                            ->label('Животные разрешены')
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
                TextColumn::make('rent_price_month')
                    ->label('Аренда')
                    ->money('RUB', 100)
                    ->sortable(),
                TextColumn::make('lease_term_min')
                    ->label('Мин. срок')
                    ->suffix(' мес'),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'rented',
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
                        'rented' => 'Сдано',
                        'archived' => 'Архив',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\RealEstate\Filament\Resources\RentalListingResource\Pages\ListRentalListings::route('/'),
        ];
    }
}
