<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Beauty\Models\Master;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class MasterResource extends Resource
{
    protected static ?string $model = Master::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Мастера';

    protected static ?string $navigationGroup = 'Beauty';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('salon_id')
                ->relationship('salon', 'name')
                ->required()
                ->label('Салон'),
            Forms\Components\TextInput::make('full_name')
                ->required()
                ->maxLength(255)
                ->label('ФИО мастера'),
            Forms\Components\KeyValue::make('specialization')
                ->label('Специализация'),
            Forms\Components\TextInput::make('experience_years')
                ->numeric()
                ->minValue(0)
                ->label('Опыт (лет)'),
            Forms\Components\TextInput::make('phone')
                ->tel()
                ->maxLength(20)
                ->label('Телефон'),
            Forms\Components\TextInput::make('rating')
                ->numeric()
                ->minValue(0)
                ->maxValue(5)
                ->default(0)
                ->label('Рейтинг'),
            Forms\Components\Toggle::make('is_active')
                ->label('Активен')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable()
                    ->sortable()
                    ->label('ФИО'),
                Tables\Columns\TextColumn::make('salon.name')
                    ->searchable()
                    ->label('Салон'),
                Tables\Columns\TextColumn::make('experience_years')
                    ->sortable()
                    ->label('Опыт (лет)'),
                Tables\Columns\TextColumn::make('rating')
                    ->sortable()
                    ->label('Рейтинг'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активен'),
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
            'index' => \App\Filament\Tenant\Resources\MasterResource\Pages\ListMasters::route('/'),
            'create' => \App\Filament\Tenant\Resources\MasterResource\Pages\CreateMaster::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\MasterResource\Pages\EditMaster::route('/{record}/edit'),
            'view' => \App\Filament\Tenant\Resources\MasterResource\Pages\ViewMaster::route('/{record}'),
        ];
    }
}
