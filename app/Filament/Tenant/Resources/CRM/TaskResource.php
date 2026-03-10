<?php

namespace App\Filament\Tenant\Resources\CRM;

use App\Models\CRM\Task;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use Filament\Tables\Actions;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'CRM';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Components\Grid::make(3)->schema([
                Components\Section::make('Задача')->schema([
                    Components\TextInput::make('title')->required()->label('Заголовок'),
                    Components\Textarea::make('description')->rows(5)->label('Описание'),
                ])->columnSpan(2),
                Components\Section::make('Параметры')->schema([
                    Components\Select::make('status')
                        ->options([
                            'new' => 'Новая',
                            'in_progress' => 'В работе',
                            'completed' => 'Завершена',
                        ])->required(),
                    Components\Select::make('priority')
                        ->options([
                            'low' => 'Низкий',
                            'medium' => 'Средний',
                            'high' => 'Высокий',
                            'critical' => 'Критический',
                        ])->required(),
                    Components\DateTimePicker::make('due_at')->label('Крайний срок'),
                    Components\Select::make('responsible_id')
                        ->relationship('responsible', 'name')->label('Исполнитель'),
                ])->columnSpan(1),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('title')->searchable()->label('Название'),
                Columns\TextColumn::make('status')->badge()->label('Статус'),
                Columns\TextColumn::make('priority')->badge()->label('Приоритет'),
                Columns\TextColumn::make('responsible.name')->label('Исполнитель'),
                Columns\TextColumn::make('due_at')->dateTime()->label('Срок'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
            'kanban' => Pages\TaskKanban::route('/kanban'),
        ];
    }
}
