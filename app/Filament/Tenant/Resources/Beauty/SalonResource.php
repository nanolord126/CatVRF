<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautySalon;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class SalonResource extends Resource
{
    protected static ?string $model = BeautySalon::class;

    protected static ?string $navigationIcon  = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Beauty';
    protected static ?string $navigationLabel = 'Салоны';
    protected static ?string $modelLabel      = 'Салон';
    protected static ?string $pluralModelLabel = 'Салоны';
    protected static ?int    $navigationSort  = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('tenant_id')
                ->default(fn () => filament()->getTenant()?->id),
            Hidden::make('uuid')
                ->default(fn () => Str::uuid()->toString()),
            Hidden::make('correlation_id')
                ->default(fn () => Str::uuid()->toString()),

            Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Название салона')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    TextInput::make('address')
                        ->label('Адрес')
                        ->required()
                        ->maxLength(500)
                        ->columnSpan(2),
                    TextInput::make('city')
                        ->label('Город')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->maxLength(20),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                    TextInput::make('website')
                        ->label('Сайт')
                        ->url()
                        ->maxLength(255),
                ]),

            Section::make('Гео-координаты')
                ->columns(2)
                ->schema([
                    TextInput::make('latitude')
                        ->label('Широта')
                        ->numeric()
                        ->step(0.0000001),
                    TextInput::make('longitude')
                        ->label('Долгота')
                        ->numeric()
                        ->step(0.0000001),
                ]),

            Section::make('Настройки')
                ->schema([
                    Toggle::make('is_verified')
                        ->label('Верифицирован')
                        ->default(false),
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                    TextInput::make('rating')
                        ->label('Рейтинг')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(5)
                        ->step(0.1)
                        ->default(0),
                ]),
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
                TextColumn::make('city')
                    ->label('Город')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Телефон'),
                TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        default       => 'danger',
                    }),
                IconColumn::make('is_verified')
                    ->label('Верифицирован')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_verified')->label('Верификация'),
                TernaryFilter::make('is_active')->label('Активность'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()?->id);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSalons::route('/'),
            'create' => Pages\CreateSalon::route('/create'),
            'edit'   => Pages\EditSalon::route('/{record}/edit'),
        ];
    }
}
