<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Models\BeautyService;
use App\Filament\Tenant\Resources\Beauty\ServiceResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * ServiceResource — Filament-ресурс услуг салона (B2B Tenant Panel).
 *
 * CRUD: создание, редактирование, B2B-цена, деактивация.
 */
final class ServiceResource extends Resource
{
    protected static ?string $model = BeautyService::class;

    protected static ?string $navigationIcon = 'heroicon-o-scissors';

    protected static ?string $navigationGroup = 'Beauty';

    protected static ?string $navigationLabel = 'Услуги';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('salon_id')
                        ->label('Салон')
                        ->relationship('salon', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),

                    Forms\Components\TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('category')
                        ->label('Категория')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('duration_minutes')
                        ->label('Длительность (мин)')
                        ->required()
                        ->numeric()
                        ->minValue(5)
                        ->maxValue(480),

                    Forms\Components\Textarea::make('description')
                        ->label('Описание')
                        ->maxLength(3000)
                        ->rows(4)
                        ->columnSpan(2),
                ]),

            Forms\Components\Section::make('Ценообразование')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('price_kopecks')
                        ->label('Цена B2C (копейки)')
                        ->required()
                        ->numeric()
                        ->minValue(100)
                        ->helperText('100 коп = 1 ₽'),

                    Forms\Components\TextInput::make('price_b2b_kopecks')
                        ->label('Цена B2B (копейки)')
                        ->numeric()
                        ->minValue(100)
                        ->helperText('Оставьте пустым, если нет B2B-цены'),
                ]),

            Forms\Components\Section::make('Дополнительно')
                ->schema([
                    Forms\Components\Toggle::make('requires_consultation')
                        ->label('Требуется консультация')
                        ->default(false),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true),

                    Forms\Components\TagsInput::make('tags')
                        ->label('Теги')
                        ->placeholder('Добавьте тег...'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('salon.name')
                    ->label('Салон')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Мин')
                    ->sortable()
                    ->suffix(' мин'),

                Tables\Columns\TextColumn::make('price_kopecks')
                    ->label('Цена B2C')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((int) $state / 100, 0, '.', ' ') . ' ₽'),

                Tables\Columns\TextColumn::make('price_b2b_kopecks')
                    ->label('Цена B2B')
                    ->formatStateUsing(fn ($state) => $state
                        ? number_format((int) $state / 100, 0, '.', ' ') . ' ₽'
                        : '—'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Категория')
                    ->options(fn () => BeautyService::distinct()->pluck('category', 'category')->toArray()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна'),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit'   => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
