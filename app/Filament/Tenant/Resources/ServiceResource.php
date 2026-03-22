<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Beauty\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-scissors';

    protected static ?string $navigationLabel = 'Услуги';

    protected static ?string $navigationGroup = 'Beauty';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('salon_id')
                ->relationship('salon', 'name')
                ->label('Салон'),
            Forms\Components\Select::make('master_id')
                ->relationship('master', 'full_name')
                ->label('Мастер'),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Название услуги'),
            Forms\Components\Textarea::make('description')
                ->maxLength(1000)
                ->label('Описание'),
            Forms\Components\TextInput::make('duration_minutes')
                ->numeric()
                ->required()
                ->minValue(1)
                ->label('Длительность (мин)'),
            Forms\Components\TextInput::make('price')
                ->numeric()
                ->required()
                ->label('Цена'),
            Forms\Components\KeyValue::make('consumables_json')
                ->label('Расходники'),
            Forms\Components\Toggle::make('is_active')
                ->label('Активна')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Название'),
                Tables\Columns\TextColumn::make('salon.name')
                    ->searchable()
                    ->label('Салон'),
                Tables\Columns\TextColumn::make('master.full_name')
                    ->searchable()
                    ->label('Мастер'),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->sortable()
                    ->label('Длительность (мин)'),
                Tables\Columns\TextColumn::make('price')
                    ->money('RUB')
                    ->sortable()
                    ->label('Цена'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активна'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('salon_id')
                    ->relationship('salon', 'name')
                    ->label('Салон'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Только активные'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);

        if (session()->has('business_card_id')) {
            $query->whereHas('salon', function ($q) {
                $q->where('business_group_id', session('business_card_id'));
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\ServiceResource\Pages\ListServices::route('/'),
            'create' => \App\Filament\Tenant\Resources\ServiceResource\Pages\CreateService::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\ServiceResource\Pages\EditService::route('/{record}/edit'),
            'view' => \App\Filament\Tenant\Resources\ServiceResource\Pages\ViewService::route('/{record}'),
        ];
    }
}
