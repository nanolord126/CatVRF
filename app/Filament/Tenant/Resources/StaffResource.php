<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Enums\StaffRole;
use App\Domains\Staff\Services\StaffRoleService;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Filament\Tenant\Resources\StaffResource\Pages\CreateStaff;
use App\Filament\Tenant\Resources\StaffResource\Pages\EditStaff;
use App\Filament\Tenant\Resources\StaffResource\Pages\ListStaff;

final class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Сотрудники';

    protected static ?string $navigationGroup = 'Business';

    protected static ?int $navigationSort = 2;

    /**
     * Visibility based on role - only Manager and above can see this resource
     */
    public static function canViewAny(): bool
    {
        $staff = self::getCurrentStaff();
        if (!$staff) {
            return false;
        }

        $role = StaffRole::tryFrom($staff->role);
        return $role?->hasPermission('staff.view_all') ?? false;
    }

    /**
     * Only Manager and above can create staff
     */
    public static function canCreate(): bool
    {
        $staff = self::getCurrentStaff();
        if (!$staff) {
            return false;
        }

        $role = StaffRole::tryFrom($staff->role);
        return $role?->hasPermission('staff.create') ?? false;
    }

    private static function getCurrentStaff(): ?Staff
    {
        $userId = Auth::id();
        if (!$userId) {
            return null;
        }
        return Staff::where('user_id', $userId)->first();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('staff_tabs')
                    ->tabs([
                        // Basic Info Tab
                        Forms\Components\Tabs\Tab::make('Основная информация')
                            ->schema([
                                Forms\Components\Section::make('Персональные данные')
                                    ->schema([
                                        Forms\Components\FileUpload::make('photo')
                                            ->label('Фото')
                                            ->image()
                                            ->directory('staff-photos')
                                            ->avatar()
                                            ->maxSize(2048)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $set('generate_video_avatar', true);
                                                }
                                            }),
                                        Forms\Components\FileUpload::make('video_avatar')
                                            ->label('Видео-аватар (4-6 сек, без звука)')
                                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/mov'])
                                            ->directory('staff-video-avatars')
                                            ->maxSize(10240)
                                            ->helperText('Загрузите видео или оно будет сгенерировано автоматически из фото')
                                            ->nullable(),
                                    ])
                                    ->columns(3),

                                Forms\Components\Section::make('Контакты')
                                    ->schema([
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Телефон')
                                            ->tel()
                                            ->maxLength(20),
                                        Forms\Components\TextInput::make('telegram')
                                            ->label('Telegram')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('whatsapp')
                                            ->label('WhatsApp')
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),
                            ]),

                        // Position & Access Tab
                        Forms\Components\Tabs\Tab::make('Должность и доступ')
                            ->schema([
                                Forms\Components\Section::make('Должность')
                                    ->schema([
                                        Forms\Components\Select::make('role')
                                            ->label('Роль')
                                            ->options([
                                                'owner' => 'Владелец',
                                                'manager' => 'Менеджер',
                                                'employee' => 'Сотрудник',
                                                'accountant' => 'Бухгалтер',
                                            ])
                                            ->required()
                                            ->default('employee'),
                                        Forms\Components\TextInput::make('position')
                                            ->label('Должность')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('department')
                                            ->label('Отдел')
                                            ->maxLength(255),
                                        Forms\Components\Select::make('manager_id')
                                            ->label('Руководитель')
                                            ->relationship('manager', 'full_name')
                                            ->searchable()
                                            ->preload(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Доступ к системе')
                                    ->schema([
                                        Forms\Components\Select::make('user_id')
                                            ->label('Пользователь системы')
                                            ->relationship('user', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),
                                        Forms\Components\KeyValue::make('permissions')
                                            ->label('Дополнительные права')
                                            ->keyLabel('Разрешение')
                                            ->valueLabel('Значение')
                                            ->addable()
                                            ->deletable(),
                                    ]),
                            ]),

                        // Employment Tab
                        Forms\Components\Tabs\Tab::make('Трудоустройство')
                            ->schema([
                                Forms\Components\Section::make('Данные о найме')
                                    ->schema([
                                        Forms\Components\DatePicker::make('hired_at')
                                            ->label('Дата найма')
                                            ->required()
                                            ->default(now()),
                                        Forms\Components\DatePicker::make('probation_end_at')
                                            ->label('Окончание испытательного срока'),
                                        Forms\Components\Select::make('employment_type')
                                            ->label('Тип занятости')
                                            ->options([
                                                'full_time' => 'Полный день',
                                                'part_time' => 'Частичная занятость',
                                                'contract' => 'По договору',
                                            ])
                                            ->default('full_time'),
                                        Forms\Components\TextInput::make('schedule')
                                            ->label('График работы')
                                            ->placeholder('Пн-Пт 9:00-18:00')
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Зарплата')
                                    ->schema([
                                        Forms\Components\TextInput::make('salary')
                                            ->label('Зарплата (руб)')
                                            ->numeric()
                                            ->prefix('₽')
                                            ->step(0.01)
                                            ->default(0),
                                        Forms\Components\Select::make('salary_type')
                                            ->label('Тип начисления')
                                            ->options([
                                                'monthly' => 'Ежемесячно',
                                                'hourly' => 'Почасово',
                                                'per_order' => 'За заказ',
                                            ])
                                            ->default('monthly'),
                                    ])
                                    ->columns(2),
                            ]),

                        // Stats Tab
                        Forms\Components\Tabs\Tab::make('Статистика')
                            ->schema([
                                Forms\Components\Section::make('Эффективность')
                                    ->schema([
                                        Forms\Components\TextInput::make('total_orders_processed')
                                            ->label('Всего заказов обработано')
                                            ->numeric()
                                            ->default(0)
                                            ->disabled(),
                                        Forms\Components\TextInput::make('total_revenue_generated')
                                            ->label('Общая выручка (руб)')
                                            ->numeric()
                                            ->prefix('₽')
                                            ->step(0.01)
                                            ->default(0)
                                            ->disabled(),
                                        Forms\Components\TextInput::make('customer_satisfaction_score')
                                            ->label('Удовлетворённость клиентов (0-5)')
                                            ->numeric()
                                            ->step(0.1)
                                            ->minValue(0)
                                            ->maxValue(5)
                                            ->default(0),
                                        Forms\Components\TextInput::make('quality_score')
                                            ->label('Качество работы (0-5)')
                                            ->numeric()
                                            ->step(0.1)
                                            ->minValue(0)
                                            ->maxValue(5)
                                            ->default(0),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Дополнительные метрики')
                                    ->schema([
                                        Forms\Components\TextInput::make('positive_reviews')
                                            ->label('Положительных отзывов')
                                            ->numeric()
                                            ->default(0),
                                        Forms\Components\TextInput::make('negative_reviews')
                                            ->label('Отрицательных отзывов')
                                            ->numeric()
                                            ->default(0),
                                        Forms\Components\TextInput::make('complaints')
                                            ->label('Жалоб')
                                            ->numeric()
                                            ->default(0),
                                        Forms\Components\TextInput::make('compliments')
                                            ->label('Комплиментов')
                                            ->numeric()
                                            ->default(0),
                                    ])
                                    ->columns(2),
                            ]),

                        // Notes Tab
                        Forms\Components\Tabs\Tab::make('Заметки')
                            ->schema([
                                Forms\Components\Section::make('Заметки')
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Общие заметки')
                                            ->rows(3),
                                        Forms\Components\Textarea::make('performance_notes')
                                            ->label('Заметки о производительности')
                                            ->rows(3),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_url')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО')
                    ->searchable(['first_name', 'last_name', 'middle_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Должность')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department')
                    ->label('Отдел')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->color(fn (string $state): string => StaffRole::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => StaffRole::tryFrom($state)?->label() ?? $state),
                Tables\Columns\TextColumn::make('video_avatar_status')
                    ->label('Видео')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'pending' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => '✓',
                        'processing' => '⏳',
                        'pending' => '○',
                        'failed' => '✗',
                        default => '–',
                    })
                    ->toggleable(
                    ->label('Роль')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'owner' => 'danger',
                        'manager' => 'warning',
                        'accountant' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'on_vacation' => 'warning',
                        'probation' => 'info',
                        'archived' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_revenue_generated')
                    ->label('Выручка')
                    ->money('RUB')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('customer_satisfaction_score')
                    ->label('Оценка клиентов')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('hired_at')
                    ->label('Дата найма')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->options([
                        'owner' => 'Владелец',
                        'manager' => 'Менеджер',
                        'employee' => 'Сотрудник',
                        'accountant' => 'Бухгалтер',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активный',
                        'inactive' => 'Неактивный',
                        'on_vacation' => 'В отпуске',
                        'probation' => 'Испытательный срок',
                        'archived' => 'В архиве',
                    ]),
                Tables\Filters\Filter::make('has_user')
                    ->label('Имеет доступ к системе')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('user_id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('archive')
                    ->label('В архив')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Архивировать сотрудника?')
                    ->modalDescription('Сотрудник будет перемещён в архив, но его данные сохранятся.')
                    ->action(function (Staff $record): void {
                        $record->archive('Архивирован через панель');
                    })
                    ->visible(fn (Staff $record): bool => !$record->is_archived),
                Tables\Actions\Action::make('restore')
                    ->label('Восстановить')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->action(function (Staff $record): void {
                        $record->restoreFromArchive();
                    })
                    ->visible(fn (Staff $record): bool => $record->is_archived),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::class,
            'create' => CreateStaff::class,
            'edit' => EditStaff::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'manager']);
    }
}
