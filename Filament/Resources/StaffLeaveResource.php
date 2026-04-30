<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * StaffLeaveResource — Layer 1: Presentation/UI Layer
 * 
 * Filament resource for Staff Leave management in Tenant Panel
 * Part of 9-layer architecture
 */
final class StaffLeaveResource extends Resource
{
    protected static ?string $model = null; // TODO: Create Eloquent model

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Отпуска';

    protected static ?string $navigationGroup = 'HRM';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Сотрудник')
                            ->relationship('employee', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Тип')
                            ->options([
                                'vacation' => 'Отпуск',
                                'sick_leave' => 'Больничный',
                                'personal' => 'Личный',
                                'unpaid' => 'Без сохранения',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Дата начала')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Дата окончания')
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Причина'),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'На рассмотрении',
                                'approved' => 'Одобрено',
                                'rejected' => 'Отклонено',
                                'cancelled' => 'Отменено',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Сотрудник')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Дата начала')
                    ->date(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Дата окончания')
                    ->date(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'vacation' => 'Отпуск',
                        'sick_leave' => 'Больничный',
                        'personal' => 'Личный',
                        'unpaid' => 'Без сохранения',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'На рассмотрении',
                        'approved' => 'Одобрено',
                        'rejected' => 'Отклонено',
                        'cancelled' => 'Отменено',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
