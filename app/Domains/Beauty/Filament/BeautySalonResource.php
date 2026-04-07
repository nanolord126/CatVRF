<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Filament;

use App\Domains\Beauty\Models\BeautySalon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class BeautySalonResource extends Resource
{
    protected static ?string $model = BeautySalon::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Салоны красоты';

    protected static ?string $pluralModelLabel = 'Салоны красоты';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label('Название')->required(),
            TextInput::make('address')->label('Адрес')->required(),
            TextInput::make('lat')->label('Широта')->numeric(),
            TextInput::make('lon')->label('Долгота')->numeric(),
            Toggle::make('is_active')->label('Активен'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->label('Название')->searchable(),
                TextColumn::make('address')->label('Адрес'),
                IconColumn::make('is_active')->label('Активен')->boolean(),
                TextColumn::make('created_at')->label('Создан')->dateTime(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Beauty\Filament\Pages\ListBeautySalons::route('/'),
        ];
    }
}