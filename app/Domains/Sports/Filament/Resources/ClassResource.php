<?php declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources;

use App\Domains\Sports\Models\ClassSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ClassResource extends Resource
{
    protected static ?string $model = ClassSession::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Классы';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Название')->required(),
            Forms\Components\RichEditor::make('description')->label('Описание'),
            Forms\Components\DateTimePickerInput::make('starts_at')->label('Начало')->required(),
            Forms\Components\DateTimePickerInput::make('ends_at')->label('Окончание')->required(),
            Forms\Components\TextInput::make('price')->label('Цена')->numeric(),
            Forms\Components\TextInput::make('max_participants')->label('Макс участников')->numeric()->required(),
            Forms\Components\Toggle::make('is_active')->label('Активен'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Класс')->searchable(),
                Tables\Columns\TextColumn::make('trainer.full_name')->label('Тренер'),
                Tables\Columns\TextColumn::make('starts_at')->label('Начало')->dateTime(),
                Tables\Columns\TextColumn::make('enrolled_count')->label('Записано'),
                Tables\Columns\TextColumn::make('price')->label('Цена')->money('RUB'),
                Tables\Columns\IconColumn::make('is_active')->label('Активен')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Sports\Filament\Resources\ClassResource\Pages\ListClasses::route('/'),
            'create' => \App\Domains\Sports\Filament\Resources\ClassResource\Pages\CreateClass::route('/create'),
            'edit' => \App\Domains\Sports\Filament\Resources\ClassResource\Pages\EditClass::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
    }
}
