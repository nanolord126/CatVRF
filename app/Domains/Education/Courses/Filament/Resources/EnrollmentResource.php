<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Filament\Resources;

use App\Domains\Education\Courses\Models\Enrollment;
use Filament\Forms\Components\{Section, Select, TextInput};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\{TextColumn, BadgeColumn, NumericColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;

final class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Обучение';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Запись')
                    ->schema([
                        Select::make('course_id')
                            ->label('Курс')
                            ->relationship('course', 'title')
                            ->required()
                            ->disabled(),
                        Select::make('student_id')
                            ->label('Студент')
                            ->relationship('student', 'name')
                            ->required()
                            ->disabled(),
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активно',
                                'completed' => 'Завершено',
                                'dropped' => 'Отклонено',
                                'paused' => 'На паузе',
                            ])
                            ->required(),
                    ]),
                Section::make('Прогресс')
                    ->schema([
                        TextInput::make('progress_percent')
                            ->label('Прогресс (%)')
                            ->numeric()
                            ->disabled(),
                        TextInput::make('total_watch_time_seconds')
                            ->label('Время просмотра (сек)')
                            ->numeric()
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')
                    ->label('Курс'),
                TextColumn::make('student.name')
                    ->label('Студент'),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'info' => 'active',
                        'success' => 'completed',
                        'danger' => 'dropped',
                        'warning' => 'paused',
                    ]),
                NumericColumn::make('progress_percent')
                    ->label('Прогресс (%)'),
                TextColumn::make('enrolled_at')
                    ->label('Записано')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status'),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }
}
