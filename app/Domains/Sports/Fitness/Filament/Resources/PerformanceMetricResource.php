<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class PerformanceMetricResource extends Resource
{

    protected static ?string $model = PerformanceMetric::class;
        protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
        protected static ?string $navigationLabel = 'Метрики';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Метрика')->schema([
                    Forms\Components\Select::make('member_id')->label('Член')->relationship('member', 'email')->required(),
                    Forms\Components\DatePickerInput::make('metric_date')->label('Дата')->required(),
                ]),
                Forms\Components\Section::make('Занятия')->schema([
                    Forms\Components\TextInput::make('classes_attended')->label('Посетил классов')->numeric(),
                    Forms\Components\TextInput::make('total_classes_available')->label('Всего доступно')->numeric(),
                ]),
                Forms\Components\Section::make('Активность')->schema([
                    Forms\Components\TextInput::make('calories_burned')->label('Сожжено калорий')->numeric(),
                    Forms\Components\TextInput::make('workout_duration_minutes')->label('Длительность (мин)')->numeric(),
                ]),
                Forms\Components\Section::make('Измерения')->schema([
                    Forms\Components\TextInput::make('body_weight')->label('Вес')->numeric(),
                    Forms\Components\TextInput::make('body_fat_percentage')->label('% жира')->numeric(),
                    Forms\Components\TextInput::make('muscle_mass')->label('Мышечная масса')->numeric(),
                ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('member.email')->label('Член')->searchable(),
                    Tables\Columns\TextColumn::make('metric_date')->label('Дата')->sortable(),
                    Tables\Columns\TextColumn::make('classes_attended')->label('Классов'),
                    Tables\Columns\TextColumn::make('calories_burned')->label('Калории'),
                    Tables\Columns\TextColumn::make('workout_duration_minutes')->label('Минут'),
                    Tables\Columns\TextColumn::make('body_weight')->label('Вес'),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('member_id')->relationship('member', 'email'),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\Sports\Fitness\Filament\Resources\PerformanceMetricResource\Pages\ListPerformanceMetrics::route('/'),
                'view' => \App\Domains\Sports\Fitness\Filament\Resources\PerformanceMetricResource\Pages\ViewPerformanceMetric::route('/{record}'),
                'edit' => \App\Domains\Sports\Fitness\Filament\Resources\PerformanceMetricResource\Pages\EditPerformanceMetric::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()?->id);
        }
}
