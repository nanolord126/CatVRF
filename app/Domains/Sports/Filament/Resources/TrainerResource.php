<?php declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class TrainerResource extends Resource
{

    protected static ?string $model = Trainer::class;
        protected static ?string $navigationIcon = 'heroicon-o-user-group';
        protected static ?string $navigationLabel = 'Тренеры';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\TextInput::make('full_name')->label('ФИО')->required(),
                Forms\Components\RichEditor::make('bio')->label('Биография'),
                Forms\Components\TextInput::make('experience_years')->label('Лет опыта')->numeric(),
                Forms\Components\TextInput::make('hourly_rate')->label('Цена/час')->numeric(),
                Forms\Components\Toggle::make('is_active')->label('Активен'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('full_name')->label('Тренер')->searchable(),
                    Tables\Columns\TextColumn::make('specializations')->label('Специализация'),
                    Tables\Columns\TextColumn::make('experience_years')->label('Опыт (лет)'),
                    Tables\Columns\TextColumn::make('rating')->label('Рейтинг'),
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
                'index' => \App\Domains\Sports\Filament\Resources\TrainerResource\Pages\ListTrainers::route('/'),
                'create' => \App\Domains\Sports\Filament\Resources\TrainerResource\Pages\CreateTrainer::route('/create'),
                'edit' => \App\Domains\Sports\Filament\Resources\TrainerResource\Pages\EditTrainer::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()?->id);
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
