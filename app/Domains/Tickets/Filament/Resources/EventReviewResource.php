<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class EventReviewResource extends Resource
{

    protected static ?string $model = EventReview::class;
        protected static ?string $navigationIcon = 'heroicon-o-star';
        protected static ?string $navigationLabel = 'Отзывы событий';
        protected static ?int $navigationSort = 5;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Отзыв')
                        ->schema([
                            Forms\Components\Select::make('event_id')
                                ->label('Событие')
                                ->relationship('event', 'title')
                                ->required(),
                            Forms\Components\Select::make('buyer_id')
                                ->label('Автор')
                                ->relationship('buyer', 'email')
                                ->required(),
                            Forms\Components\Slider::make('rating')
                                ->label('Рейтинг')
                                ->min(1)
                                ->max(5)
                                ->step(1)
                                ->required(),
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\RichEditor::make('content')
                                ->label('Текст отзыва')
                                ->required(),
                            Forms\Components\Toggle::make('verified_purchase')
                                ->label('Проверенная покупка'),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('event.title')
                        ->label('Событие')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('buyer.email')
                        ->label('Автор')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('rating')
                        ->label('Рейтинг')
                        ->sortable()
                        ->formatStateUsing(fn($state) => "⭐ $state"),
                    Tables\Columns\TextColumn::make('title')
                        ->label('Заголовок')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\IconColumn::make('verified_purchase')
                        ->label('Проверено')
                        ->boolean(),
                    Tables\Columns\TextColumn::make('published_at')
                        ->label('Опубликовано')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('rating')
                        ->label('Рейтинг')
                        ->options([
                            '5' => '⭐⭐⭐⭐⭐ 5',
                            '4' => '⭐⭐⭐⭐ 4',
                            '3' => '⭐⭐⭐ 3',
                            '2' => '⭐⭐ 2',
                            '1' => '⭐ 1',
                        ]),
                    Tables\Filters\TernaryFilter::make('verified_purchase')
                        ->label('Только проверенные'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListEventReviews::route('/'),
                'create' => Pages\CreateEventReview::route('/create'),
                'edit' => Pages\EditEventReview::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()?->id)
                ->with(['event', 'buyer']);
        }
}
