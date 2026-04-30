<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * StaffAchievementResource — Layer 1: Presentation/UI Layer
 * 
 * Filament resource for Staff Achievements management in Tenant Panel
 * Part of 9-layer architecture
 */
final class StaffAchievementResource extends Resource
{
    protected static ?string $model = null; // TODO: Create Eloquent model

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Достижения';

    protected static ?string $navigationGroup = 'HRM';

    protected static ?int $navigationSort = 4;

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
                            ->label('Иконка'),
                        Forms\Components\Select::make('category')
                            ->label('Категория')
                            ->options([
                                'sales' => 'Продажи',
                                'performance' => 'Производительность',
                                'teamwork' => 'Командная работа',
                                'innovation' => 'Инновации',
                                'customer_service' => 'Обслуживание клиентов',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('points')
                            ->label('Очки')
                            ->numeric()
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активно')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->badge(),
                Tables\Columns\TextColumn::make('points')
                    ->label('Очки')
                    ->numeric(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'sales' => 'Продажи',
                        'performance' => 'Производительность',
                        'teamwork' => 'Командная работа',
                        'innovation' => 'Инновации',
                        'customer_service' => 'Обслуживание клиентов',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активно'),
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
