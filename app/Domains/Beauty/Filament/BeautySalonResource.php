<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautySalonResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Section, TextInput, Textarea, Toggle};
    use Filament\Tables\Columns\{TextColumn, BooleanColumn};
    use Filament\Tables\Actions\{Action, DeleteAction, EditAction, ViewAction};
    use Filament\Tables\Filters\{Filter, TrashedFilter};

    /**
     * Filament Resource для салонов красоты.
     * Production 2026.
     */
    final class BeautySalonResource extends Resource
    {
        protected static ?string $model = BeautySalon::class;

        protected static ?string $navigationIcon = 'heroicon-o-sparkles';

        protected static ?string $navigationLabel = 'Салоны красоты';

        protected static ?string $pluralModelLabel = 'Салоны красоты';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основное')
                    ->schema([
                        TextInput::make('name')
                            ->label('Название салона')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('address')
                            ->label('Адрес')
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),
                    ])
                    ->columns(2),
                Section::make('Статус')
                    ->schema([
                        Toggle::make('is_verified')
                            ->label('Верифицирован'),
                    ])
                    ->columns(1),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')
                        ->label('Название')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('address')
                        ->label('Адрес')
                        ->searchable(),
                    TextColumn::make('rating')
                        ->label('Рейтинг')
                        ->sortable(),
                    BooleanColumn::make('is_verified')
                        ->label('Верифицирован'),
                ])
                ->filters([
                    TrashedFilter::make(),
                ])
                ->actions([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->bulkActions([
                    // Bulk actions here
                ]);
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => ListRecords::class,
                'create' => CreateRecord::class,
                'edit' => EditRecord::class,
                'view' => ViewRecord::class,
            ];
        }
}
