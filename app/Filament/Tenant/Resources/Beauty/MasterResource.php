<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Models\Master;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Beauty Master Resource (Layer 7)
 * 
 * Включает Tenant Scoping, UUID-генерацию, привязку к салону и Fraud Check.
 */
final class MasterResource extends Resource
{
    protected static ?string $model = Master::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Мастера';
    protected static ?string $navigationGroup = 'Beauty & Wellness';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Персональные данные мастера')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->default(fn () => (string) Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\TextInput::make('full_name')
                            ->label('ФИО')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Введите ФИО мастера'),
                        Forms\Components\Select::make('salon_id')
                            ->label('Основной салон')
                            ->relationship(
                                'salon', 
                                'name', 
                                fn (Builder $query) => $query->where('tenant_id', tenant('id'))
                            )
                            ->required()
                            ->preload(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->nullable(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Специализация и квалификация')
                    ->description('Навыки и опыт мастера')
                    ->schema([
                        Forms\Components\TagsInput::make('specialization')
                            ->label('Специализация (навыки)')
                            ->placeholder('Добавьте навыки через запятую')
                            ->required()
                            ->helperText('Например: стрижка, окрашивание, укладка'),
                        Forms\Components\TextInput::make('experience_years')
                            ->label('Стаж работы (лет)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(70),
                        Forms\Components\RichEditor::make('bio')
                            ->label('Биография')
                            ->columnSpanFull()
                            ->placeholder('Расскажите о себе и своём опыте'),
                    ])->columns(2),

                Forms\Components\Section::make('Расписание')
                    ->description('Время работы мастера')
                    ->schema([
                        Forms\Components\TextInput::make('schedule')
                            ->label('Основное расписание')
                            ->placeholder('Пн-Пт: 10:00-20:00, Сб: 10:00-18:00')
                            ->nullable(),
                        Forms\Components\TextInput::make('break_time')
                            ->label('Время обеда')
                            ->placeholder('13:00-14:00')
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Услуги')
                    ->description('Список услуг, которые предоставляет мастер')
                    ->schema([
                        Forms\Components\Repeater::make('services')
                            ->label('Услуги')
                            ->relationship('services')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Название услуги')
                                    ->required(),
                                Forms\Components\TextInput::make('price')
                                    ->label('Цена (руб)')
                                    ->numeric()
                                    ->required(),
                                Forms\Components\TextInput::make('duration_minutes')
                                    ->label('Длительность (мин)')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->collapsed(),
                    ]),

                Forms\Components\Section::make('Портфолио')
                    ->description('Фото работ и примеры')
                    ->schema([
                        Forms\Components\FileUpload::make('portfolio_images')
                            ->label('Фото работ')
                            ->multiple()
                            ->directory('masters/portfolio')
                            ->visibility('public'),
                    ]),

                Forms\Components\Section::make('Статус')
                    ->description('Настройки активности')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Мастер активен')
                            ->default(true),
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Верифицирован')
                            ->default(false),
                        Forms\Components\TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => tenant('id')),
                Forms\Components\Hidden::make('correlation_id')
                    ->default(fn () => (string) Str::uuid()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('salon.name')
                    ->label('Салон')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('specialization')
                    ->label('Специализация')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->limit(40),
                Tables\Columns\TextColumn::make('experience_years')
                    ->label('Стаж (лет)')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\BadgeColumn::make('rating')
                    ->label('Рейтинг')
                    ->numeric(1)
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'info',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('review_count')
                    ->label('Отзывы')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\BooleanColumn::make('is_active')
                    ->label('Активен')
                    ->sortable(),
                Tables\Columns\BooleanColumn::make('is_verified')
                    ->label('Верифицирован')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата регистрации')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('salon_id')
                    ->label('Салон')
                    ->relationship('salon', 'name', fn (Builder $query) => $query->where('tenant_id', tenant('id'))),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Верифицирован'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Активировать')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Деактивировать')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant('id'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMasters::route('/'),
            'create' => Pages\CreateMaster::route('/create'),
            'view' => Pages\ViewMaster::route('/{record}'),
            'edit' => Pages\EditMaster::route('/{record}/edit'),
        ];
    }
}
