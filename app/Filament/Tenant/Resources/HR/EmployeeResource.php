<?php

namespace App\Filament\Tenant\Resources\HR;

use App\Filament\Tenant\Resources\HR\EmployeeResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EmployeeResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $slug = 'hr/employees';
    protected static ?string $label = 'Employee';
    protected static ?string $pluralLabel = 'Employees';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('role_code');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('hr_trust_score')
                            ->label('Trust Score (AI)')
                            ->disabled()
                            ->prefix('⭐')
                            ->numeric(),
                        Forms\Components\TextInput::make('completed_tasks_count')
                            ->label('Смен на бирже')
                            ->disabled(),
                        Forms\Components\Select::make('role_code')
                            ->options([
                                'MASTER' => 'Master',
                                'HOUSEKEEPER' => 'Housekeeper',
                                'ADMIN' => 'Administrator',
                                'MANAGER' => 'Manager',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Employment Details')
                    ->schema([
                        Forms\Components\DatePicker::make('hired_at'),
                        Forms\Components\DatePicker::make('fired_at'),
                        Forms\Components\Textarea::make('address')
                            ->rows(3),
                        Forms\Components\KeyValue::make('geo_location')
                            ->keyLabel('Coordinate')
                            ->valueLabel('Value'),
                    ])->columns(2),

                Forms\Components\Section::make('Payroll Integration')
                    ->description('Links to Payroll module configuration')
                    ->schema([
                        Forms\Components\Placeholder::make('payroll_link')
                            ->content(fn ($record) => $record ? new \Illuminate\Support\HtmlString("<a href='/admin/employee-payroll-configs?tableFilters[user][value]={$record->id}' class='text-primary-600 underline'>View Salary Config</a>") : 'Save employee first'),
                    ]),
                
                Forms\Components\Section::make('История медицинских посещений (Human)')
                    ->description('Список личных обращений пользователя в клиники сети')
                    ->schema([
                        Forms\Components\ViewField::make('medical_history')
                            ->view('filament.tenant.resources.hr.employee.modals.visit-history')
                            ->label('')
                            ->hidden(fn ($record) => !$record)
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EmployeeResource\RelationManagers\PetsRelationManager::class,
            EmployeeResource\RelationManagers\HealthChecklistRelationManager::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('role_code')
                    ->colors([
                        'primary' => 'MASTER',
                        'warning' => 'HOUSEKEEPER',
                        'success' => 'ADMIN',
                        'danger' => 'MANAGER',
                    ]),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('hired_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_code')
                    ->options([
                        'MASTER' => 'Master',
                        'HOUSEKEEPER' => 'Housekeeper',
                        'ADMIN' => 'Administrator',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
