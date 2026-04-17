<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Books;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class BooksResource extends Resource
{

    protected static ?string $model = Book::class;
        protected static ?string $navigationIcon = 'heroicon-o-book-open';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')->label('Название книги')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('isbn')->label('ISBN')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('isbn13')->label('ISBN-13')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('original_title')->label('Оригинальное название')->columnSpan(2),
                    ]),

                Section::make('Автор и издатель')
                    ->columns(2)
                    ->schema([
                        TextInput::make('author')->label('Автор')->required()->maxLength(255)->columnSpan(1),
                        TextInput::make('author_bio')->label('Биография автора')->columnSpan(2),
                        TextInput::make('publisher')->label('Издательство')->maxLength(255)->columnSpan(1),
                        TextInput::make('publication_year')->label('Год издания')->numeric()->columnSpan(1),
                        TextInput::make('original_language')->label('Оригинальный язык')->columnSpan(1),
                        TextInput::make('translator')->label('Переводчик (если есть)')->columnSpan(1),
                    ]),

                Section::make('Жанр и категория')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Select::make('main_genre')->label('Основной жанр')->options([
                            'fiction' => 'Художественная',
                            'non-fiction' => 'Нехудожественная',
                            'science-fiction' => 'Научная фантастика',
                            'fantasy' => 'Фэнтези',
                            'mystery' => 'Детектив',
                            'romance' => 'Романтика',
                            'horror' => 'Хоррор',
                            'biography' => 'Биография',
                            'history' => 'История',
                            'self-help' => 'Саморазвитие',
                        ])->required()->columnSpan(1),
                        TagsInput::make('sub_genres')->label('Поджанры')->columnSpan(2),
                        TagsInput::make('keywords')->label('Ключевые слова')->columnSpan(2),
                    ]),

                Section::make('Описание и содержание')
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                        RichEditor::make('table_of_contents')->label('Оглавление')->columnSpan('full'),
                    ]),

                Section::make('Технические характеристики')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('page_count')->label('Количество страниц')->numeric()->columnSpan(1),
                        TextInput::make('word_count')->label('Количество слов')->numeric()->columnSpan(1),
                        TextInput::make('language')->label('Язык')->columnSpan(1),
                        Select::make('format')->label('Формат')->options([
                            'hardcover' => 'Твёрдая обложка',
                            'paperback' => 'Мягкая обложка',
                            'ebook' => 'Электронная',
                            'audiobook' => 'Аудиокнига'
                        ])->multiple()->columnSpan(2),
                        TextInput::make('print_length')->label('Размер печати (см)')->columnSpan(1),
                        TextInput::make('weight_grams')->label('Вес (гр)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Цена и доступность')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')->label('Цена (₽)')->numeric()->columnSpan(1),
                        TextInput::make('discount_percent')->label('Скидка (%)')->numeric()->columnSpan(1),
                        Toggle::make('in_stock')->label('В наличии')->columnSpan(1),
                        TextInput::make('stock_quantity')->label('Количество')->numeric()->columnSpan(1),
                        Toggle::make('has_free_shipping')->label('Бесплатная доставка')->columnSpan(1),
                        TextInput::make('shipping_days')->label('Срок доставки (дн)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Рейтинги и отзывы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('average_rating')->label('Средний рейтинг (0-5)')->numeric()->step(0.1)->min(0)->max(5)->columnSpan(1),
                        TextInput::make('review_count')->label('Количество отзывов')->numeric()->columnSpan(1),
                        TextInput::make('goodreads_rating')->label('GoodReads рейтинг')->numeric()->step(0.1)->columnSpan(1),
                        TextInput::make('awards')->label('Награды/Номинации')->columnSpan(2),
                    ]),

                Section::make('Форматы и интеграции')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_audiobook')->label('Есть аудиокнига')->columnSpan(1),
                        Toggle::make('has_ebook')->label('Есть E-book')->columnSpan(1),
                        TextInput::make('audible_url')->label('Audible ссылка')->url()->columnSpan(1),
                        TextInput::make('kindle_url')->label('Kindle ссылка')->url()->columnSpan(1),
                        TextInput::make('liters_url')->label('ЛитРес ссылка')->url()->columnSpan(2),
                        TagsInput::make('available_platforms')->label('Доступные платформы')->columnSpan(2),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('cover_image')->label('Обложка')->image()->directory('books-covers'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('books-gallery')->columnSpan('full'),
                        FileUpload::make('sample_excerpt')->label('Отрывок (PDF)')->acceptedFileTypes(['application/pdf'])->directory('books-excerpts'),
                    ]),

                Section::make('Рекомендации и похожие книги')
                    ->collapsed()
                    ->schema([
                        Repeater::make('similar_books')
                            ->label('Похожие книги')
                            ->columns(2)
                            ->schema([
                                TextInput::make('book_id')->label('ID книги'),
                                TextInput::make('title')->label('Название'),
                            ])->columnSpan('full'),
                    ]),

                Section::make('SEO')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('meta_title')->label('Meta Title')->maxLength(60),
                        Textarea::make('meta_description')->label('Meta Description')->maxLength(160)->rows(2)->columnSpan(2),
                        TagsInput::make('meta_keywords')->label('Meta Keywords')->columnSpan(2),
                        TextInput::make('slug')->label('Slug')->columnSpan(2),
                    ]),

                Section::make('Управление')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')->label('Активна')->default(true),
                        Toggle::make('is_featured')->label('Избранная')->default(false),
                        Toggle::make('verified')->label('Проверена')->default(false),
                        TextInput::make('priority')->label('Приоритет')->numeric()->columnSpan(2),
                        DatePicker::make('published_at')->label('Публикация')->columnSpan(1),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                ImageColumn::make('cover_image')->label('Обложка')->size(50),
                TextColumn::make('title')->label('Название')->searchable()->sortable()->weight('bold')->limit(40),
                TextColumn::make('author')->label('Автор')->searchable()->sortable(),
                TextColumn::make('main_genre')->label('Жанр')->badge()->color('info'),
                TextColumn::make('average_rating')->label('⭐ Рейтинг')->numeric(digits: 1)->badge()->color('warning'),
                BadgeColumn::make('in_stock')->label('В наличии')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('has_audiobook')->label('Аудио')->colors(['secondary' => true, 'gray' => false]),
                BadgeColumn::make('has_ebook')->label('E-book')->colors(['secondary' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранная')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('price')->label('Цена (₽)')->numeric()->sortable()->badge()->color('success'),
                TextColumn::make('page_count')->label('Стр.')->numeric()->badge(),
                TextColumn::make('publication_year')->label('Год')->numeric()->sortable()->badge(),
            ])->filters([
                SelectFilter::make('main_genre')->options([
                    'fiction' => 'Художественная',
                    'non-fiction' => 'Нехудожественная',
                    'science-fiction' => 'Научная фантастика',
                    'fantasy' => 'Фэнтези',
                    'mystery' => 'Детектив',
                    'romance' => 'Романтика',
                    'horror' => 'Хоррор',
                    'biography' => 'Биография',
                    'history' => 'История',
                    'self-help' => 'Саморазвитие',
                ]),
                Filter::make('in_stock')->query(fn (Builder $q) => $q->where('in_stock', true)),
                Filter::make('has_audiobook')->query(fn (Builder $q) => $q->where('has_audiobook', true))->label('Есть аудиокнига'),
                Filter::make('has_ebook')->query(fn (Builder $q) => $q->where('has_ebook', true))->label('Есть E-book'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListBooks::route('/'),
                'create' => Pages\CreateBooks::route('/create'),
                'edit' => Pages\EditBooks::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
