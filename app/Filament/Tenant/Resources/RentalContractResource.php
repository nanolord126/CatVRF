<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RentalContractResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = RentalContract::class;

        protected static ?string $navigationIcon = 'heroicon-o-document-text';

        protected static ?string $navigationGroup = 'Недвижимость';

        protected static ?string $label = 'Договор аренды';

        protected static ?string $pluralLabel = 'Договоры аренды';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Детали контракта')
                        ->description('Юридические и финансовые условия аренды')
                        ->schema([
                            Forms\Components\Select::make('listing_id')
                                ->relationship('listing', 'title')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Объявление')
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state) {
                                        $listing = \App\Domains\RealEstate\Models\Listing::find($state);
                                        if ($listing) {
                                            $set('rent_amount', $listing->price);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListRentalContract::route('/'),
                'create' => Pages\\CreateRentalContract::route('/create'),
                'edit' => Pages\\EditRentalContract::route('/{record}/edit'),
                'view' => Pages\\ViewRentalContract::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListRentalContract::route('/'),
                'create' => Pages\\CreateRentalContract::route('/create'),
                'edit' => Pages\\EditRentalContract::route('/{record}/edit'),
                'view' => Pages\\ViewRentalContract::route('/{record}'),
            ];
        }
}
