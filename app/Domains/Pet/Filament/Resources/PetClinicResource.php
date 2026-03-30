<?php declare(strict_types=1);

namespace App\Domains\Pet\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetClinicResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = PetClinic::class;

        protected static ?string $slug = 'pet-clinics';

        protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

        protected static ?string $navigationGroup = 'Pet Services';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->maxLength(1000),
                    TextInput::make('address')
                        ->required()
                        ->maxLength(500),
                    TextInput::make('phone')
                        ->required()
                        ->tel(),
                    TextInput::make('email')
                        ->email()
                        ->required(),
                    TextInput::make('license_number')
                        ->unique(ignoreRecord: true)
                        ->maxLength(100),
                    Toggle::make('is_verified')
                        ->label('Verified'),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('owner.name')
                        ->label('Owner')
                        ->searchable(),
                    TextColumn::make('phone')
                        ->searchable(),
                    TextColumn::make('rating')
                        ->numeric()
                        ->sortable(),
                    BooleanColumn::make('is_verified')
                        ->sortable(),
                    BooleanColumn::make('is_active')
                        ->sortable(),
                ])
                ->filters([
                    //
                ])
                ->actions([
                    // Actions
                ])
                ->bulkActions([
                    // Bulk Actions
                ]);
        }
}
