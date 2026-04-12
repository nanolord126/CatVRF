<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Channels;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class ChannelResource extends Resource
{

    protected static ?string $model = BusinessChannel::class;

        protected static ?string $navigationIcon = 'heroicon-o-megaphone';

        protected static ?string $navigationGroup = 'Мой канал';

        protected static ?string $navigationLabel = 'Настройки канала';

        protected static ?string $modelLabel = 'Канал';

        protected static ?string $pluralModelLabel = 'Каналы';

        protected static ?int $navigationSort = 1;

        // ──────────────────────────────────────────────────────
        // Form
        // ──────────────────────────────────────────────────────

        public static function form(Form $form): Form
        {
            return $form->schema([

                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название канала')
                            ->required()
                            ->maxLength(256),

                        Forms\Components\Textarea::make('description')
                            ->label('Описание канала')
                            ->maxLength(2000)
                            ->rows(3),

                        Forms\Components\TextInput::make('slug')
                            ->label('URL-адрес канала')
                            ->prefix('/channel/')
                            ->helperText('Автоматически генерируется из названия')
                            ->disabled()
                            ->maxLength(128),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Медиа')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label('Аватар канала')
                            ->image()
                            ->imageEditor()
                            ->directory('channels/avatars')
                            ->maxSize(5120),

                        Forms\Components\FileUpload::make('cover_url')
                            ->label('Обложка канала')
                            ->image()
                            ->imageEditor()
                            ->directory('channels/covers')
                            ->maxSize(10240),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Статистика')
                    ->schema([
                        Forms\Components\Placeholder::make('subscribers_count')
                            ->label('Подписчиков')
                            ->content(fn (?BusinessChannel $record) => $record?->subscribers_count ?? 0),

                        Forms\Components\Placeholder::make('posts_count')
                            ->label('Постов всего')
                            ->content(fn (?BusinessChannel $record) => $record?->posts_count ?? 0),

                        Forms\Components\Placeholder::make('plan')
                            ->label('Текущий тариф')
                            ->content(fn (?BusinessChannel $record) => $record?->plan?->name ?? 'Без тарифа'),

                        Forms\Components\Placeholder::make('plan_expires_at')
                            ->label('Тариф действует до')
                            ->content(fn (?BusinessChannel $record) => $record?->plan_expires_at?->format('d.m.Y') ?? '—'),
                    ])
                    ->columns(2)
                    ->visibleOn('edit'),

                Forms\Components\Section::make('Подписка на тариф')
                    ->schema([
                        Forms\Components\Select::make('plan_slug_select')
                            ->label('Выберите тарифный план')
                            ->options([
                                'basic'    => 'Базовый (49₽/мес) — 2 поста/день, 5 фото',
                                'extended' => 'Расширенный (199₽/мес) — 5 постов/день, опросы, промо, статистика',
                            ])
                            ->helperText('Оплата списывается с кошелька бизнеса')
                            ->dehydrated(false),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('subscribe_to_plan')
                                ->label('Оформить / продлить подписку')
                                ->icon('heroicon-o-credit-card')
                                ->color('success')
                                ->action(function (Forms\Get $get, ?BusinessChannel $record): void {
                                    $planSlug = $get('plan_slug_select');

                                    if (!$planSlug || !$record) {
                                        $this->notification->make()->title('Выберите тарифный план')->warning()->send();
                                        return;
                                    }

                                    try {
                                        app(ChannelTariffService::class)->subscribe(
                                            $record,
                                            $planSlug,
                                            Str::uuid()->toString()
                                        );

                                        $this->notification->make()
                                            ->title('Подписка оформлена')
                                            ->success()
                                            ->send();
                                    } catch (\Throwable $e) {
                                        $this->notification->make()
                                            ->title('Ошибка: ' . $e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
        }

        // ──────────────────────────────────────────────────────
        // Table
        // ──────────────────────────────────────────────────────

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\ImageColumn::make('avatar_url')
                        ->label('Аватар')
                        ->circular(),

                    Tables\Columns\TextColumn::make('name')
                        ->label('Канал')
                        ->searchable()
                        ->weight('bold'),

                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            'archived'  => 'warning',
                            'suspended' => 'danger',
                            default     => 'gray',
                        }),

                    Tables\Columns\TextColumn::make('plan.name')
                        ->label('Тариф')
                        ->default('Без тарифа'),

                    Tables\Columns\TextColumn::make('subscribers_count')
                        ->label('Подписчиков')
                        ->numeric(),

                    Tables\Columns\TextColumn::make('posts_count')
                        ->label('Постов')
                        ->numeric(),

                    Tables\Columns\TextColumn::make('plan_expires_at')
                        ->label('Тариф до')
                        ->date('d.m.Y')
                        ->placeholder('—'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('restore')
                        ->label('Восстановить')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn (BusinessChannel $record) => $record->isArchived())
                        ->action(function (BusinessChannel $record): void {
                            app(ChannelService::class)->restoreChannel($record);
                            $this->notification->make()->title('Канал восстановлен')->success()->send();
                        }),
                ])
                ->defaultSort('created_at', 'desc');
        }

        // ──────────────────────────────────────────────────────
        // Query scoping (tenant isolation)
        // ──────────────────────────────────────────────────────

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes()
                ->where('tenant_id', filament()->getTenant()?->id ?? '0')
                ->with(['plan']);
        }

        public static function getPages(): array
        {
            return [
                'index'  => \App\Filament\Tenant\Resources\Channels\Pages\ListChannels::route('/'),
                'create' => \App\Filament\Tenant\Resources\Channels\Pages\CreateChannel::route('/create'),
                'edit'   => \App\Filament\Tenant\Resources\Channels\Pages\EditChannel::route('/{record}/edit'),
            ];
        }
}
