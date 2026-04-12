<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class BeverageReviewResource extends Resource
{

    protected static ?string $model = BeverageReview::class;

        protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
        protected static ?string $navigationGroup = 'Beverage Management';
        protected static ?int $navigationSort = 5;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Subject Discovery')
                        ->description('Identify which entity (Shop or Item) is being reviewed.')
                        ->columns(3)
                        ->schema([
                            Forms\Components\Select::make('user_id')
                                ->relationship('user', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('shop_id')
                                ->relationship('shop', 'name')
                                ->label('Venue Reviewed')
                                ->searchable()
                                ->required(fn (Forms\Get $get) => empty($get('beverage_item_id'))),
                            Forms\Components\Select::make('beverage_item_id')
                                ->relationship('item', 'name')
                                ->label('Drink Item Reviewed')
                                ->searchable()
                                ->required(fn (Forms\Get $get) => empty($get('shop_id'))),
                        ]),

                    Forms\Components\Section::make('Feedback Metrics')
                        ->description('Consumer sentiment scores and verbose feedback.')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('rating')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(5)
                                ->required()
                                ->label('Rating (1-5 Star Protocol)'),
                            Forms\Components\TextInput::make('beverage_order_id')
                                ->numeric()
                                ->label('Associated Transaction ID')
                                ->placeholder('Verification source'),
                            Forms\Components\Textarea::make('comment')
                                ->required()
                                ->rows(5)
                                ->label('Verbatim Feedback'),
                            Forms\Components\FileUpload::make('media_json')
                                ->multiple()
                                ->image()
                                ->label('Proof of Consumption (Photos)'),
                        ]),

                    Forms\Components\Section::make('Administrative Content Controls')
                        ->description('Trust & Safety moderation status.')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Toggle::make('is_verified')
                                ->label('Verified Purchase/Experience')
                                ->inline(false)
                                ->onIcon('heroicon-o-check-badge'),
                            Forms\Components\Toggle::make('is_visible')
                                ->label('Active on Marketplace')
                                ->default(true)
                                ->inline(false),
                        ]),

                    Forms\Components\Section::make('System Metadata')
                        ->description('Auditing and analytical tracking.')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('correlation_id')
                                ->disabled()
                                ->label('Audit Correlation ID'),
                            Forms\Components\KeyValue::make('tags')
                                ->label('Behavioral Analytical Tags'),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListBeverageReview::route('/'),
                'create' => Pages\CreateBeverageReview::route('/create'),
                'edit' => Pages\EditBeverageReview::route('/{record}/edit'),
                'view' => Pages\ViewBeverageReview::route('/{record}'),
            ];
        }
}
