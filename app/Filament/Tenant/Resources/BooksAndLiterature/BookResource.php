<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BooksAndLiterature;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Form, Components\Section, Components\TextInput, Components\Select, Components\Toggle, Components\Hidden, Components\RichEditor, Components\FileUpload};
    use Filament\Resources\Resource;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter, Filters\Filter};
    use Filament\Tables\Actions\{EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class BookResource extends Resource
    {
        protected static ?string $model = Book::class;
        protected static ?string $navigationIcon = 'heroicon-m-book-open';
        protected static ?string $navigationGroup = 'Books & Media';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('📖 Основная информация')
                    ->icon('heroicon-m-book-open')
                    ->schema([
                        TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('title')->label('Название')->required()->columnSpan(2),
                        TextInput::make('author')->label('Автор')->required()->columnSpan(2),
                        TextInput::make('isbn')->label('ISBN')->unique()->columnSpan(1),
                        TextInput::make('publisher')->label('Издатель')->columnSpan(1),
                        Select::make('genre')->label('Жанр')->options(['fiction' => 'Художество', 'nonfiction' => 'Нехудож.', 'science' => 'Наука', 'history' => 'История', 'children' => 'Детская', 'educational' => 'Образование'])->required()->columnSpan(1),
                        Select::make('language')->label('Язык')->options(['ru' => 'Русский', 'en' => 'Английский', 'fr' => 'Французский'])->required()->columnSpan(1),
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->columnSpan(1),
                        RichEditor::make('description')->label('Аннотация')->columnSpan('full'),
                        FileUpload::make('cover_image')->label('Обложка')->image()->directory('books')->columnSpan(1),
                    ])->columns(4),

                Section::make('Характеристики')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        TextInput::make('page_count')->label('Страниц')->numeric()->columnSpan(1),
                        TextInput::make('publication_year')->label('Год издания')->numeric()->columnSpan(1),
                        Select::make('binding')->label('Переплёт')->options(['hardcover' => 'Твёрдый', 'paperback' => 'Мягкий', 'ebook' => 'Электронная'])->columnSpan(1),
                        Toggle::make('has_audiobook')->label('🎧 Есть аудиокнига')->columnSpan(1),
                    ])->columns(4),

                Section::make('Рейтинг и статус')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextColumn::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        Toggle::make('is_active')->label('Активный')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Рекомендуемый')->columnSpan(1),
                        Toggle::make('is_bestseller')->label('🔥 Бестселлер')->columnSpan(1),
                    ])->columns(4),

                Section::make('Служебная информация')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Hidden::make('tenant_id')->default(fn () => tenant('id')),
                        Hidden::make('correlation_id')->default(fn () => Str::uuid()),
                    ])->columns('full'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('title')->label('Название')->searchable()->sortable()->limit(40),
                TextColumn::make('author')->label('Автор')->searchable()->limit(25),
                BadgeColumn::make('genre')->label('Жанр')->color(fn ($state) => match($state) { 'fiction' => 'blue', 'nonfiction' => 'green', 'science' => 'purple', 'history' => 'orange', 'children' => 'pink', default => 'gray' }),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('rating')->label('★')->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                BooleanColumn::make('has_audiobook')->label('🎧')->toggleable(),
                BooleanColumn::make('is_featured')->label('⭐')->toggleable(),
                BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
            ])->filters([
                SelectFilter::make('genre')->label('Жанр')->options(['fiction' => 'Художество', 'nonfiction' => 'Нехудож.', 'science' => 'Наука', 'history' => 'История'])->multiple(),
                TernaryFilter::make('has_audiobook')->label('С аудиокнигой'),
                TernaryFilter::make('is_featured')->label('Рекомендуемые'),
                TrashedFilter::make(),
            ])->actions([
                ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]),
            ])->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('activate')->label('Активировать')->icon('heroicon-m-check-circle')->color('success')->action(function ($records) { $records->each(fn ($r) => $r->update(['is_active' => true])); })->deselectRecordsAfterCompletion()]),
            ])->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\BooksAndLiterature\Pages\ListBooks::route('/'),
                'create' => \App\Filament\Tenant\Resources\BooksAndLiterature\Pages\CreateBook::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\BooksAndLiterature\Pages\EditBook::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}
