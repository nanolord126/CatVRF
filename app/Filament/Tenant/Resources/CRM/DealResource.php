<?php

namespace App\Filament\Tenant\Resources\CRM;

use App\Models\CRM\Deal;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use Filament\Tables\Actions;

class DealResource extends Resource
{
    protected static ?string $model = Deal::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'CRM';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Components\Section::make('Основная информация')
                ->schema([
                    Components\TextInput::make('name')->required()->label('Название сделки'),
                    Components\Select::make('pipeline_id')
                        ->relationship('pipeline', 'name'),
                    Components\Select::make('stage_id')
                        ->relationship('stage', 'name'),
                    Components\TextInput::make('amount')
                        ->numeric()->prefix('₽')->label('Сумма'),
                    Components\Select::make('user_id')
                        ->relationship('owner', 'name')->label('Ответственный'),
                ])->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('name')->searchable()->label('Сделка'),
                Columns\TextColumn::make('stage.name')->badge()->label('Этап'),
                Columns\TextColumn::make('amount')->money('rub')->sortable()->label('Сумма'),
                Columns\TextColumn::make('owner.name')->label('Ответственный'),
                Columns\TextColumn::make('created_at')->dateTime()->label('Создана'),
            ])
            ->filters([
                // Pipeline filters
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeals::route('/'),
            'create' => Pages\CreateDeal::route('/create'),
            'edit' => Pages\EditDeal::route('/{record}/edit'),
            'kanban' => Pages\DealKanban::route('/kanban'),
        ];
    }
}
