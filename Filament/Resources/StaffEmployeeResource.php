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
 * StaffEmployeeResource — Layer 1: Presentation/UI Layer
 * 
 * Filament resource for Staff management in Tenant Panel
 * Part of 9-layer architecture
 */
final class StaffEmployeeResource extends Resource
{
    protected static ?string $model = null; // TODO: Create Eloquent model

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Сотрудники';

    protected static ?string $navigationGroup = 'HRM';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('Имя')
                            ->required(),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Фамилия')
                            ->required(),
                        Forms\Components\TextInput::make('middle_name')
                            ->label('Отчество'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->required(),
                        Forms\Components\Select::make('role')
                            ->label('Роль')
                            ->options([
                                'admin' => 'Администратор',
                                'manager' => 'Менеджер',
                                'specialist' => 'Специалист',
                                'junior' => 'Младший специалист',
                                'intern' => 'Стажер',
                                'contractor' => 'Контрактор',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активен',
                                'on_leave' => 'В отпуске',
                                'terminated' => 'Уволен',
                                'suspended' => 'Приостановлен',
                                'probation' => 'Испытательный срок',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Должность')
                    ->schema([
                        Forms\Components\TextInput::make('position')
                            ->label('Должность'),
                        Forms\Components\TextInput::make('department')
                            ->label('Отдел'),
                        Forms\Components\Select::make('manager_id')
                            ->label('Руководитель')
                            ->relationship('manager', 'full_name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Геймификация')
                    ->schema([
                        Forms\Components\TextInput::make('level')
                            ->label('Уровень')
                            ->numeric()
                            ->default(1),
                        Forms\Components\TextInput::make('experience_points')
                            ->label('Очки опыта')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО')
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Должность'),
                Tables\Columns\TextColumn::make('department')
                    ->label('Отдел'),
                Tables\Columns\TextColumn::make('role')
                    ->label('Роль')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'on_leave' => 'warning',
                        'terminated' => 'danger',
                        'suspended' => 'danger',
                        'probation' => 'info',
                    }),
                Tables\Columns\TextColumn::make('level')
                    ->label('Уровень')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Администратор',
                        'manager' => 'Менеджер',
                        'specialist' => 'Специалист',
                        'junior' => 'Младший специалист',
                        'intern' => 'Стажер',
                        'contractor' => 'Контрактор',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Активен',
                        'on_leave' => 'В отпуске',
                        'terminated' => 'Уволен',
                        'suspended' => 'Приостановлен',
                        'probation' => 'Испытательный срок',
                    ]),
                Tables\Filters\SelectFilter::make('department')
                    ->options([
                        'sales' => 'Продажи',
                        'marketing' => 'Маркетинг',
                        'support' => 'Поддержка',
                        'it' => 'IT',
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
