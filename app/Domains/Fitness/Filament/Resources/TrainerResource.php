<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources;

use App\Domains\Fitness\Models\Trainer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TrainerResource extends Resource
{
    protected static ?string $model = Trainer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Тренеры';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Профиль')->schema([
                Forms\Components\Select::make('gym_id')->label('Клуб')->relationship('gym', 'name')->nullable(),
                Forms\Components\TextInput::make('full_name')->label('Имя')->required(),
                Forms\Components\RichEditor::make('bio')->label('Биография'),
                Forms\Components\TextInput::make('phone')->label('Телефон'),
                Forms\Components\TextInput::make('certification_url')->label('Сертификат'),
            ]),
            Forms\Components\Section::make('Параметры')->schema([
                Forms\Components\TextInput::make('experience_years')->label('Опыт (лет)')->numeric(),
                Forms\Components\TextInput::make('hourly_rate')->label('Ставка/час')->numeric(),
                Forms\Components\Toggle::make('is_verified')->label('Проверен'),
                Forms\Components\Toggle::make('is_active')->label('Активен'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label('Тренер')->searchable(),
                Tables\Columns\TextColumn::make('gym.name')->label('Клуб'),
                Tables\Columns\TextColumn::make('experience_years')->label('Опыт'),
                Tables\Columns\TextColumn::make('rating')->label('Рейтинг')->sortable(),
                Tables\Columns\TextColumn::make('class_count')->label('Классов'),
                Tables\Columns\IconColumn::make('is_verified')->label('Проверен')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('Активен')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')->label('Проверённые'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Fitness\Filament\Resources\TrainerResource\Pages\ListTrainers::route('/'),
            'create' => \App\Domains\Fitness\Filament\Resources\TrainerResource\Pages\CreateTrainer::route('/create'),
            'edit' => \App\Domains\Fitness\Filament\Resources\TrainerResource\Pages\EditTrainer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
    }
}
