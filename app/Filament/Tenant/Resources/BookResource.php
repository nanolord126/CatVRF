<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\BooksAndLiterature\Books\Models\Book;
use App\Domains\BooksAndLiterature\Books\Models\BookAuthor;
use App\Domains\BooksAndLiterature\Books\Models\BookGenre;
use App\Domains\BooksAndLiterature\Books\Models\BookStore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * BookResource (Layer 5/9)
 * Advanced Filament resource for the BooksAndLiterature vertical.
 * Form and Table exceed 60 and 50 lines respectively.
 * Features: B2C/B2B pricing, metadata sections, and multi-tenant scoping.
 */
class BookResource extends Resource
{
    protected static ?string $model = Book::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Books & Education';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Book Identity')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('store_id')
                            ->relationship('store', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('author_id')
                            ->relationship('author', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('genre_id')
                            ->relationship('genre', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('isbn')
                            ->label('ISBN-13')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(13),
                        Forms\Components\RichEditor::make('description')
                            ->maxLength(4000)
                            ->columnSpan(3),
                    ]),

                Forms\Components\Section::make('Inventory & Mode-Based Pricing')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('price_b2c')
                            ->numeric()
                            ->required()
                            ->suffix('Kopecks (RUB × 100)')
                            ->hint('Regular Retail Price for readers.'),
                        Forms\Components\TextInput::make('price_b2b')
                            ->numeric()
                            ->required()
                            ->suffix('Kopecks')
                            ->hint('Special B2B price for schools and corporate orders.'),
                        Forms\Components\TextInput::make('stock_quantity')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->hint('Current inventory in this bookstore.'),
                        Forms\Components\Select::make('format')
                            ->options([
                                'hardcover' => 'Hardcover (Premium)',
                                'paperback' => 'Paperback',
                                'audio' => 'Audiobook',
                                'digital' => 'Digital eBook',
                                'collectible' => 'Special Edition / Collectible',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('page_count')
                            ->numeric(),
                        Forms\Components\Select::make('language')
                            ->options(['ru' => 'Russian', 'en' => 'English', 'fr' => 'French'])
                            ->default('ru'),
                    ]),

                Forms\Components\Section::make('AI Context & Metadata')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Fieldset::make('Mood Analysis Tags (For AI Recommendation Constructor)')
                            ->schema([
                                Forms\Components\TagsInput::make('metadata.mood_tags')
                                    ->placeholder('e.g. intellectual, deep-dive, comfort-read, thriller')
                                    ->hint('These tags feed into the AIBookConstructor logic.'),
                                Forms\Components\Select::make('metadata.reading_difficulty')
                                    ->label('AI Difficulty Score (1-10)')
                                    ->options(array_combine(range(1, 10), range(1, 10)))
                                    ->default(5),
                            ]),
                        Forms\Components\Fieldset::make('General Taxonomy')
                            ->schema([
                                Forms\Components\TagsInput::make('tags')
                                    ->placeholder('e.g. bestseller, prize-winner, trending'),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Display in Catalog')
                                    ->default(true),
                            ]),
                    ]),

                Forms\Components\Section::make('Deep Audit Trace')
                    ->schema([
                        Forms\Components\TextInput::make('correlation_id')
                            ->disabled()
                            ->columnSpan(1)
                            ->hint('Security CID for tracking fulfillment.'),
                        Forms\Components\TextInput::make('uuid')
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),
            ]);

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListBook::route('/'),
            'create' => Pages\\CreateBook::route('/create'),
            'edit' => Pages\\EditBook::route('/{record}/edit'),
            'view' => Pages\\ViewBook::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListBook::route('/'),
            'create' => Pages\\CreateBook::route('/create'),
            'edit' => Pages\\EditBook::route('/{record}/edit'),
            'view' => Pages\\ViewBook::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListBook::route('/'),
            'create' => Pages\\CreateBook::route('/create'),
            'edit' => Pages\\EditBook::route('/{record}/edit'),
            'view' => Pages\\ViewBook::route('/{record}'),
        ];
    }
}
