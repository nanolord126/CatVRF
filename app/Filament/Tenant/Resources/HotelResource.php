<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Hotels\Models\Hotel;
use App\Filament\Tenant\Resources\HotelResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * КАНОН 2026: Hotel Resource (Layer 7)
 * 
 * Управление отелями в Tenant панели.
 * Обязательно: tenant scoping через global scope (в модели).
 */
final class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Hotels & Booking';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->maxLength(500),
                        Forms\Components\KeyValue::make('schedule_json')
                            ->label('Расписание'),
                    ])->columns(2),

                Forms\Components\Section::make('Статус и Рейтинг')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Верифицирован'),
                        Forms\Components\TextInput::make('rating')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('Геопозиция')
                    ->schema([
                        Forms\Components\TextInput::make('lat')
                            ->numeric(),
                        Forms\Components\TextInput::make('lon')
                            ->numeric(),
                    ])->columns(2),

                Forms\Components\Section::make('Теги и Мета')
                    ->schema([
                        Forms\Components\TagsInput::make('tags'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->wrap()
                    ->limit(50),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активен'),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Вериф.'),
                Tables\Columns\TextColumn::make('rating')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('rooms_count')
                    ->counts('rooms')
                    ->label('Номеров'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('is_verified'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers for Rooms, Bookings, B2BContracts will be added later
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHotels::route('/'),
            'create' => Pages\CreateHotel::route('/create'),
            'edit' => Pages\EditHotel::route('/{record}/edit'),
        ];
    }
}
