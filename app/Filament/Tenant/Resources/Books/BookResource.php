<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Books;

    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables;
    use Filament\Tables\Columns\{TextColumn, BadgeColumn, BooleanColumn, ImageColumn};
    use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter};
    use Filament\Tables\Actions\{ActionGroup, ViewAction, EditAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class BookResource extends Resource
    {
        protected static ?string $model = \App\Domains\Books\Models\Book::class;
        protected static ?string $navigationIcon = 'heroicon-o-book-open';
        protected static ?string $navigationGroup = 'Books';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-cube')
                    ->schema([
                        TextInput::make('uuid')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('title')->label('Название')->required()->columnSpan(2),
                        TextInput::make('isbn')->label('ISBN')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('author')->label('Автор')->required()->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        TextInput::make('publisher')->label('Издательство')->columnSpan(2),
                        TextInput::make('publication_year')->label('Год издания')->numeric()->columnSpan(2),
                        FileUpload::make('cover_image')->label('Обложка')->image()->directory('books')->columnSpan(1),
                    ])->columns(4),

                Section::make('Категория и жанр')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('category')
                            ->label('Категория')
                            ->options([
                                'fiction' => '📖 Художественная',
                                'nonfiction' => '📚 Научная',
                                'biography' => '👤 Биография',
                                'fantasy' => '⚔️ Фантастика',
                                'mystery' => '🔍 Детектив',
                                'romance' => '💕 Романс',
                                'children' => '👶 Детская',
                                'educational' => '🎓 Образование',
                            ])
                            ->required()
                            ->columnSpan(2),

                        TagsInput::make('genres')->label('Жанры')->columnSpan('full'),

                        Select::make('language')->label('Язык')->options(['russian' => 'Русский', 'english' => 'Английский', 'french' => 'Французский', 'spanish' => 'Испанский'])->required()->columnSpan(2),

                        TextInput::make('pages_count')->label('Страниц')->numeric()->columnSpan(2),
                    ])->columns(4),

                Section::make('Формат и специальные издания')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        Select::make('format')
                            ->label('Формат')
                            ->options([
                                'hardcover' => 'Твёрдый переплёт',
                                'paperback' => 'Мягкий переплёт',
                                'ebook' => 'Электронная книга',
                                'audiobook' => 'Аудиокнига',
                            ])
                            ->required()
                            ->columnSpan(2),

                        Toggle::make('is_first_edition')->label('1-е издание')->columnSpan(1),

                        Toggle::make('is_signed')->label('Подписано автором')->columnSpan(1),

                        TextInput::make('print_run')->label('Тираж')->columnSpan(2),

                        TextInput::make('book_condition')->label('Состояние')->columnSpan(2),
                    ])->columns(4),

                Section::make('Цена и наличие')
                    ->icon('heroicon-m-banknote')
                    ->schema([
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->suffix('₽')->columnSpan(2),
                        TextInput::make('current_stock')->label('На складе')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('min_stock_threshold')->label('Мин. запас')->numeric()->columnSpan(1),
                    ])->columns(4),

                Section::make('Рейтинг и отзывы')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextInput::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('review_count')->label('Отзывов')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('download_count')->label('Скачиваний (для e-book)')->numeric()->disabled()->columnSpan(2),
                    ])->columns(4),

                Section::make('Управление')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Toggle::make('is_active')->label('Активен')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Рекомендуемая')->columnSpan(1),
                        Toggle::make('is_bestseller')->label('🏆 Бестселлер')->columnSpan(2),
                    ])->columns(4),

                Section::make('Служебная')
                    ->schema([
                        Hidden::make('tenant_id')->default(fn () => tenant('id')),
                        Hidden::make('correlation_id')->default(fn () => Str::uuid()),
                        Hidden::make('business_group_id')->default(fn () => filament()->getTenant()?->active_business_group_id),
                    ]),
            ]);
        }

        public static function table(Tables\Table $table): Tables\Table
        {
            return $table->columns([
                ImageColumn::make('cover_image')->label('Обложка')->height(60),
                TextColumn::make('title')->label('Название')->searchable()->sortable()->limit(35),
                TextColumn::make('author')->label('Автор')->searchable()->limit(20),
                BadgeColumn::make('category')->label('Категория')->color('info'),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('rating')->label('⭐')->badge()->color(fn ($state) => $state >= 4.2 ? 'success' : 'warning'),
                TextColumn::make('review_count')->label('Отзывов')->numeric(),
                BadgeColumn::make('format')->label('Формат')->color(fn ($state) => $state === 'ebook' ? 'primary' : 'gray'),
                BooleanColumn::make('is_bestseller')->label('🏆'),
                BooleanColumn::make('is_active')->label('Активен')->toggleable()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Категория')->options(['fiction' => 'Художественная', 'nonfiction' => 'Научная', 'fantasy' => 'Фантастика'])->multiple(),
                SelectFilter::make('format')->label('Формат')->options(['hardcover' => 'Твёрдый', 'paperback' => 'Мягкий', 'ebook' => 'E-book'])->multiple(),
                SelectFilter::make('language')->label('Язык')->options(['russian' => 'Русский', 'english' => 'Английский'])->multiple(),
                TernaryFilter::make('is_bestseller')->label('Бестселлер'),
                TrashedFilter::make(),
            ])
            ->actions([ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()])])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('mark_bestseller')->label('Отметить как бестселлер')->action(fn ($records) => $records->each(fn ($r) => $r->update(['is_bestseller' => true])))->deselectRecordsAfterCompletion()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Books\BookResource\Pages\ListBooks::route('/'),
                'create' => \App\Filament\Tenant\Resources\Books\BookResource\Pages\CreateBook::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Books\BookResource\Pages\EditBook::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}
