<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Dental\Infrastructure\Models\ToothChartModel;
use Modules\Dental\Livewire\DentalChart;

final class ToothChartResource extends Resource
{
    protected static ?string $model = ToothChartModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-tooth';
    protected static ?string $navigationLabel = 'Зубные карты';
    protected static ?string $navigationGroup = 'Стоматология';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('patient_id')
                            ->relationship('patient', 'name')
                            ->searchable()
                            ->required()
                            ->label('Пациент'),
                        Forms\Components\Select::make('doctor_id')
                            ->relationship('doctor', 'name')
                            ->searchable()
                            ->label('Врач'),
                        Forms\Components\Select::make('numbering_system')
                            ->options([
                                'fdi' => 'FDI (ISO 3950)',
                                'universal' => 'Universal (US)',
                            ])
                            ->default('fdi')
                            ->required()
                            ->label('Система нумерации'),
                        Forms\Components\Toggle::make('is_primary')
                            ->default(true)
                            ->label('Постоянные зубы')
                            ->helperText('Отключите для молочных зубов'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Пациент')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Врач')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('numbering_system')
                    ->label('Нумерация')
                    ->colors([
                        'primary' => 'fdi',
                        'secondary' => 'universal',
                    ]),
                Tables\Columns\IconColumn::make('is_primary')
                    ->boolean()
                    ->label('Тип'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('numbering_system')
                    ->options([
                        'fdi' => 'FDI',
                        'universal' => 'Universal',
                    ]),
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Тип зубов')
                    ->placeholder('Все')
                    ->trueLabel('Постоянные')
                    ->falseLabel('Молочные'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'teeth' => Tables\Columns\TextColumn::make('teeth'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Dental\Filament\Resources\ToothChartResource\Pages\ListToothCharts::route('/'),
            'create' => \Modules\Dental\Filament\Resources\ToothChartResource\Pages\CreateToothChart::route('/create'),
            'view' => \Modules\Dental\Filament\Resources\ToothChartResource\Pages\ViewToothChart::route('/{record}'),
            'edit' => \Modules\Dental\Filament\Resources\ToothChartResource\Pages\EditToothChart::route('/{record}/edit'),
        ];
    }
}
