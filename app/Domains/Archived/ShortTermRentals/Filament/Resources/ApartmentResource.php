<?php declare(strict_types=1);

namespace App\Domains\Archived\ShortTermRentals\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ApartmentResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Apartment::class;


        protected static ?string $navigationGroup = 'Посуточная аренда';


        public static function form(Forms\Form $form): Forms\Form


        {


            return $form->schema([


                Forms\Components\TextInput::make('name')->required(),


                Forms\Components\TextInput::make('address')->required(),


                Forms\Components\TextInput::make('rooms')->numeric()->required(),


                Forms\Components\TextInput::make('area_sqm')->numeric()->required(),


                Forms\Components\TextInput::make('price_per_night')->numeric()->required(),


                Forms\Components\TextInput::make('deposit_amount')->numeric()->required(),


                Forms\Components\Toggle::make('is_active')->default(true),


            ]);


        }


        public static function table(Tables\Table $table): Tables\Table


        {


            return $table->columns([


                Tables\Columns\TextColumn::make('name'),


                Tables\Columns\TextColumn::make('address')->limit(30),


                Tables\Columns\TextColumn::make('price_per_night')->money('RUB'),


                Tables\Columns\IconColumn::make('is_active')->boolean(),


            ])->filters([])


                ->actions([Tables\Actions\EditAction::make()])


                ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);


        }


        public static function getPages(): array


        {


            return [


                'index' => \App\Domains\Archived\ShortTermRentals\Filament\Resources\ApartmentResource\Pages\ListApartments::route('/'),


                'create' => \App\Domains\Archived\ShortTermRentals\Filament\Resources\ApartmentResource\Pages\CreateApartment::route('/create'),


                'edit' => \App\Domains\Archived\ShortTermRentals\Filament\Resources\ApartmentResource\Pages\EditApartment::route('/{record}/edit'),


            ];


        }
}
