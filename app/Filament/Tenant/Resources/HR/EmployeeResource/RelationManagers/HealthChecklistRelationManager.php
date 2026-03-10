<?php

namespace App\Filament\Tenant\Resources\HR\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Common\HealthRecommendation;

class HealthChecklistRelationManager extends RelationManager
{
    protected static string $relationship = 'healthRecommendations';

    protected static ?string $title = 'Чеклист Здоровья (Checklist)';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->label('Название задачи'),
                Forms\Components\Select::make('frequency')
                    ->options([
                        'ONCE' => 'Разово',
                        'DAILY' => 'Ежедневно',
                        'WEEKLY' => 'Еженедельно',
                        'MONTHLY' => 'Ежемесячно',
                    ])
                    ->required()
                    ->label('Частота'),
                Forms\Components\DatePicker::make('next_due_date')
                    ->required()
                    ->label('Дата выполнения'),
                Forms\Components\Toggle::make('is_completed')
                    ->label('Выполнено'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Задача')
                    ->description(fn (HealthRecommendation $record): string => $record->description ?? ''),
                Tables\Columns\BadgeColumn::make('frequency')
                    ->label('Частота')
                    ->colors([
                        'danger' => 'DAILY',
                        'warning' => 'WEEKLY',
                        'primary' => 'MONTHLY',
                    ]),
                Tables\Columns\TextColumn::make('next_due_date')
                    ->date()
                    ->label('Срок'),
                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean()
                    ->label('Статус'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
