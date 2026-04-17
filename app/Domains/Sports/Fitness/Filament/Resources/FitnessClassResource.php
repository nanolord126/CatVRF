<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Filament\Resources;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class FitnessClassResource extends Resource
{

    protected static ?string $model = FitnessClass::class;
        protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
        protected static ?string $navigationLabel = 'Занятия';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Занятие')->schema([
                    Forms\Components\Select::make('gym_id')->label('Клуб')->relationship('gym', 'name')->required(),
                    Forms\Components\Select::make('trainer_id')->label('Тренер')->relationship('trainer', 'full_name')->required(),
                    Forms\Components\TextInput::make('name')->label('Название')->required(),
                    Forms\Components\RichEditor::make('description')->label('Описание'),
                ]),
                Forms\Components\Section::make('Параметры')->schema([
                    Forms\Components\TextInput::make('class_type')->label('Тип')->required(),
                    Forms\Components\TextInput::make('duration_minutes')->label('Длительность (мин)')->numeric(),
                    Forms\Components\TextInput::make('max_participants')->label('Макс участников')->numeric(),
                    Forms\Components\TextInput::make('price_per_class')->label('Цена за класс')->numeric(),
                ]),
                Forms\Components\Section::make('Статус')->schema([
                    Forms\Components\Toggle::make('is_active')->label('Активно'),
                ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')->label('Занятие')->searchable(),
                    Tables\Columns\TextColumn::make('gym.name')->label('Клуб'),
                    Tables\Columns\TextColumn::make('trainer.full_name')->label('Тренер'),
                    Tables\Columns\TextColumn::make('price_per_class')->label('Цена')->money('RUB'),
                    Tables\Columns\TextColumn::make('rating')->label('Рейтинг')->sortable(),
                    Tables\Columns\IconColumn::make('is_active')->label('Активно')->boolean(),
                ])
                ->filters([
                    Tables\Filters\TernaryFilter::make('is_active')->label('Активные'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\Sports\Fitness\Filament\Resources\FitnessClassResource\Pages\ListFitnessClasses::route('/'),
                'create' => \App\Domains\Sports\Fitness\Filament\Resources\FitnessClassResource\Pages\CreateFitnessClass::route('/create'),
                'edit' => \App\Domains\Sports\Fitness\Filament\Resources\FitnessClassResource\Pages\EditFitnessClass::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()?->id);
        }
}
