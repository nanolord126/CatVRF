<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Medical::class;

        protected static ?string $slug = 'medicals';

        protected static ?string $recordTitleAttribute = 'name';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->label('Medical Service Name'),
                    TextInput::make('description')
                        ->maxLength(500)
                        ->label('Description'),
                    TextInput::make('price')
                        ->numeric()
                        ->minValue(0)
                        ->label('Price'),
                    Textarea::make('notes')
                        ->maxLength(1000)
                        ->columnSpan('full'),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('description')
                        ->limit(50)
                        ->sortable(),
                    TextColumn::make('price')
                        ->sortable(),
                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    //
                ])
                ->actions([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->bulkActions([
                    DeleteBulkAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListMedical::route('/'),
                'create' => Pages\CreateMedical::route('/create'),
                'edit' => Pages\EditMedical::route('/{record}/edit'),
                'view' => Pages\ViewMedical::route('/{record}'),
            ];
        }
}
