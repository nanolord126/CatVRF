<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HR;

use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'HR';
    protected static ?string $navigationLabel = 'Сотрудники';
    protected static ?string $modelLabel = 'Сотрудник';
    protected static ?string $pluralModelLabel = 'Сотрудники';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('tenant_id')
                ->default(fn (): int|string => filament()->getTenant()?->id),
            Hidden::make('correlation_id')
                ->default(fn (): string => Str::uuid()->toString()),

            Section::make('Личная информация')
                ->columns(2)
                ->schema([
                    TextInput::make('full_name')
                        ->label('ФИО')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    Select::make('position')
                        ->label('Должность')
                        ->options([
                            'courier'   => 'Курьер',
                            'master'    => 'Мастер',
                            'manager'   => 'Менеджер',
                            'driver'    => 'Водитель',
                            'cashier'   => 'Кассир',
                            'admin'     => 'Администратор',
                            'warehouse' => 'Складской работник',
                        ])
                        ->required()
                        ->searchable()
                        ->columnSpan(1),
                    Select::make('employment_type')
                        ->label('Тип занятости')
                        ->options([
                            'full_time'  => 'Полная занятость',
                            'part_time'  => 'Частичная занятость',
                            'contract'   => 'Контракт',
                            'freelance'  => 'Фриланс',
                        ])
                        ->required()
                        ->columnSpan(1),
                ]),

            Section::make('Зарплата и даты')
                ->columns(2)
                ->schema([
                    TextInput::make('base_salary_kopecks')
                        ->label('Оклад (коп.)')
                        ->helperText('Вводите в копейках: 50 000 ₽ = 5 000 000 коп.')
                        ->numeric()
                        ->required()
                        ->columnSpan(1),
                    DatePicker::make('hire_date')
                        ->label('Дата найма')
                        ->required()
                        ->default(now()->toDateString())
                        ->columnSpan(1),
                    DatePicker::make('termination_date')
                        ->label('Дата увольнения')
                        ->nullable()
                        ->columnSpan(1),
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true)
                        ->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('ФИО')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('position')
                    ->label('Должность')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'master'    => 'info',
                        'courier'   => 'warning',
                        'admin'     => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('employment_type')
                    ->label('Занятость')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'part_time'  => 'info',
                        'contract'   => 'warning',
                        'freelance'  => 'gray',
                        default      => 'gray',
                    }),
                TextColumn::make('base_salary_kopecks')
                    ->label('Оклад')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2) . ' ₽'),
                TextColumn::make('hire_date')
                    ->label('Принят')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('termination_date')
                    ->label('Уволен')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('position')
                    ->label('Должность')
                    ->options([
                        'courier'   => 'Курьер',
                        'master'    => 'Мастер',
                        'manager'   => 'Менеджер',
                        'driver'    => 'Водитель',
                        'cashier'   => 'Кассир',
                        'admin'     => 'Администратор',
                        'warehouse' => 'Складской работник',
                    ]),
                SelectFilter::make('employment_type')
                    ->label('Тип занятости')
                    ->options([
                        'full_time'  => 'Полная занятость',
                        'part_time'  => 'Частичная занятость',
                        'contract'   => 'Контракт',
                        'freelance'  => 'Фриланс',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Статус')
                    ->options(['1' => 'Активные', '0' => 'Уволенные']),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('dismiss')
                    ->label('Уволить')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->visible(fn (Employee $record): bool => $record->is_active)
                    ->requiresConfirmation()
                    ->action(fn (Employee $record) => $record->update([
                        'is_active'        => false,
                        'termination_date' => now()->toDateString(),
                    ])),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->defaultSort('full_name');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()?->id);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Tenant\Resources\HR\EmployeeResource\Pages\ListEmployees::route('/'),
            'create' => \App\Filament\Tenant\Resources\HR\EmployeeResource\Pages\CreateEmployee::route('/create'),
            'view'   => \App\Filament\Tenant\Resources\HR\EmployeeResource\Pages\ViewEmployee::route('/{record}'),
            'edit'   => \App\Filament\Tenant\Resources\HR\EmployeeResource\Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
