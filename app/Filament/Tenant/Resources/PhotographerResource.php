<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Photography\Models\Photographer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — PHOTOGRAPHER RESOURCE
 * ПЛОТНОСТЬ КОДА > 60 СТРОК
 */
final class PhotographerResource extends Resource
{
    protected static ?string $model = Photographer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    
    protected static ?string $navigationGroup = 'Photography';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Профиль Фотографа')
                    ->description('Укажите персональные данные и специализацию мастера.')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label('ФИО')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('salon_id')
                            ->label('Студия / Салон')
                            ->relationship('studio', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('experience_years')
                            ->label('Стаж (лет)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(50),

                        Forms\Components\Toggle::make('is_available')
                            ->label('Доступен для бронирования')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Специализация и Навыки')
                    ->schema([
                        Forms\Components\TagsInput::make('specialization')
                            ->label('Специализации')
                            ->placeholder('Портрет, Wedding, Fashion, Food')
                            ->required(),

                        Forms\Components\TextInput::make('rating')
                            ->label('Рейтинг')
                            ->default(5.0)
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Скрытые параметры')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->default(fn () => (string) Str::uuid())
                            ->disabled()
                            ->dehydrated(),
                            
                        Forms\Components\TextInput::make('correlation_id')
                            ->label('Correlation ID')
                            ->default(fn () => (string) Str::uuid())
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Системные теги'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Фотограф')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('studio.name')
                    ->label('Место работы')
                    ->default('Фриланс')
                    ->sortable(),

                Tables\Columns\TextColumn::make('experience_years')
                    ->label('Опыт (лет)')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_available')
                    ->label('Доступен')
                    ->boolean(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('★')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('studio_id')
                    ->label('По студии')
                    ->relationship('studio', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\PhotographerResource\Pages\ListPhotographers::route('/'),
            'create' => \App\Filament\Tenant\Resources\PhotographerResource\Pages\CreatePhotographer::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\PhotographerResource\Pages\EditPhotographer::route('/{record}/edit'),
        ];
    }
}
