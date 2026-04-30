<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * StaffBadgeResource — Layer 1: Presentation/UI Layer
 * 
 * Filament resource for Badge management
 */
final class StaffBadgeResource extends Resource
{
    protected static ?string $model = \Modules\CatCRM\Domain\Staff\Models\BadgeModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-ribbon';

    protected static ?string $navigationLabel = 'Бейджи';

    protected static ?string $navigationGroup = 'HRM - Геймификация';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->required(),
                        Forms\Components\TextInput::make('icon')
                            ->label('Иконка (emoji или URL)')
                            ->required(),
                        Forms\Components\Select::make('category')
                            ->label('Категория')
                            ->options([
                                'performance' => 'Производительность',
                                'learning' => 'Обучение',
                                'social' => 'Социальное',
                                'wellness' => 'Благополучие',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('points_required')
                            ->label('Очки для получения')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Textarea::make('condition')
                            ->label('Условие получения')
                            ->required(),
                    ]),
                Forms\Components\Section::make('Настройки')
                    ->schema([
                        Forms\Components\Toggle::make('is_rare')
                            ->label('Редкий бейдж'),
                        Forms\Components\Toggle::make('is_legendary')
                            ->label('Легендарный бейдж'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                Tables\Columns\TextColumn::make('icon')
                    ->label('Иконка'),
                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->badge(),
                Tables\Columns\TextColumn::make('points_required')
                    ->label('Очки'),
                Tables\Columns\IconColumn::make('is_rare')
                    ->label('Редкий')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus'),
                Tables\Columns\IconColumn::make('is_legendary')
                    ->label('Легендарный')
                    ->boolean()
                    ->trueIcon('heroicon-o-fire')
                    ->falseIcon('heroicon-o-minus'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'performance' => 'Производительность',
                        'learning' => 'Обучение',
                        'social' => 'Социальное',
                        'wellness' => 'Благополучие',
                    ]),
                Tables\Filters\TernaryFilter::make('is_rare')
                    ->label('Редкие'),
                Tables\Filters\TernaryFilter::make('is_legendary')
                    ->label('Легендарные'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
