<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Channels;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class PostResource extends Resource
{

    protected static ?string $model = Post::class;

        protected static ?string $navigationIcon = 'heroicon-o-newspaper';

        protected static ?string $navigationGroup = 'Мой канал';

        protected static ?string $navigationLabel = 'Посты';

        protected static ?string $modelLabel = 'Пост';

        protected static ?string $pluralModelLabel = 'Посты';

        protected static ?int $navigationSort = 2;

        // ──────────────────────────────────────────────────────
        // Form
        // ──────────────────────────────────────────────────────

        public static function form(Form $form): Form
        {
            return $form->schema([

                Forms\Components\Section::make('Содержание')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->maxLength(512)
                            ->placeholder('Необязательно'),

                        Forms\Components\RichEditor::make('content')
                            ->label('Текст поста')
                            ->required()
                            ->maxLength(10000)
                            ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList', 'blockquote'])
                            ->columnSpanFull(),

                        Forms\Components\Select::make('visibility')
                            ->label('Видимость')
                            ->options([
                                'all' => 'Все (B2C + B2B)',
                                'b2c' => 'Только клиенты (B2C)',
                                'b2b' => 'Только бизнес (B2B)',
                            ])
                            ->default('all')
                            ->required(),

                        Forms\Components\Toggle::make('is_promo')
                            ->label('Промо-материал')
                            ->helperText('Доступно только в тарифе "Расширенный"'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Медиафайлы')
                    ->schema([
                        Forms\Components\FileUpload::make('media_upload')
                            ->label('Фото / Видео / Shorts')
                            ->multiple()
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4'])
                            ->maxSize(50 * 1024) // 50MB
                            ->directory('channels/media')
                            ->helperText('Максимум 5 файлов для базового тарифа, 20 для расширенного'),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Section::make('Публикация')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft'               => 'Черновик',
                                'pending_moderation'  => 'На модерации',
                                'published'           => 'Опубликован',
                                'archived'            => 'В архиве',
                            ])
                            ->default('draft')
                            ->required()
                            ->disabled(fn ($record) => $record !== null && in_array($record?->status, ['published', 'rejected'])),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Отложенная публикация')
                            ->helperText('Доступно только в тарифе "Расширенный"')
                            ->minDate(now()->addMinutes(5)),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Опрос')
                    ->schema([
                        Forms\Components\KeyValue::make('poll')
                            ->label('Вопрос и варианты ответов')
                            ->helperText('Доступно только в тарифе "Расширенный". Формат: {"question":"...","options":["A","B","C"]}'),
                    ])
                    ->collapsed()
                    ->columnSpanFull(),

            ]);
        }

        // ──────────────────────────────────────────────────────
        // Table
        // ──────────────────────────────────────────────────────

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('title')
                        ->label('Заголовок')
                        ->default('—')
                        ->limit(50)
                        ->searchable(),

                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'draft'              => 'gray',
                            'pending_moderation' => 'warning',
                            'rejected'           => 'danger',
                            'archived'           => 'secondary',
                            default              => 'gray',
                        }),

                    Tables\Columns\TextColumn::make('visibility')
                        ->label('Видимость')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'b2c' => 'success',
                            'b2b' => 'warning',
                            default => 'gray',
                        }),

                    Tables\Columns\TextColumn::make('views_count')
                        ->label('Просмотры')
                        ->numeric()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('reactions_count')
                        ->label('Реакции')
                        ->numeric()
                        ->sortable(),

                    Tables\Columns\IconColumn::make('is_promo')
                        ->label('Промо')
                        ->boolean(),

                    Tables\Columns\TextColumn::make('published_at')
                        ->label('Опубликован')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('scheduled_at')
                        ->label('Запланирован')
                        ->dateTime('d.m.Y H:i')
                        ->placeholder('—'),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'draft'              => 'Черновик',
                            'pending_moderation' => 'На модерации',
                            'published'          => 'Опубликован',
                            'archived'           => 'Архив',
                            'rejected'           => 'Отклонён',
                        ]),

                    Tables\Filters\SelectFilter::make('visibility')
                        ->label('Видимость')
                        ->options(['all' => 'Все', 'b2c' => 'B2C', 'b2b' => 'B2B']),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('archive')
                        ->label('В архив')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Post $record) => $record->status === 'published')
                        ->action(function (Post $record): void {
                            app(PostService::class)->archivePost($record, Str::uuid()->toString());
                            $this->notification->make()->title('Пост перенесён в архив')->success()->send();
                        }),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
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
                ->whereHas('channel', fn ($q) => $q
                    ->where('tenant_id', filament()->getTenant()?->id ?? '0')
                )
                ->with(['channel:id,name,slug', 'media']);
        }

        public static function getPages(): array
        {
            return [
                'index'  => \App\Filament\Tenant\Resources\Channels\Pages\ListPosts::route('/'),
                'create' => \App\Filament\Tenant\Resources\Channels\Pages\CreatePost::route('/create'),
                'edit'   => \App\Filament\Tenant\Resources\Channels\Pages\EditPost::route('/{record}/edit'),
            ];
        }
}
