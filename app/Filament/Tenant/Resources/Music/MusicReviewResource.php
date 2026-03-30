<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicReviewResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = MusicReview::class;

        protected static ?string $navigationIcon = 'heroicon-o-star';

        protected static ?string $navigationGroup = 'Music & Instruments';

        protected static ?string $modelLabel = 'Review';

        protected static ?string $pluralModelLabel = 'Reviews';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Customer Feedback')
                        ->description('Rating and review content')
                        ->schema([
                            Forms\Components\RatingStar::make('rating')
                                ->required()
                                ->label('Rating (1-5)')
                                ->default(5),

                            Forms\Components\Select::make('user_id')
                                ->label('Reviewer (Customer)')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->required(),

                            Forms\Components\Textarea::make('comment')
                                ->required()
                                ->maxLength(65535)
                                ->columnSpanFull(),
                        ])->columns(2),

                    Forms\Components\Section::make('Linked Entity')
                        ->description('Associate this review with a specific item or service')
                        ->schema([
                            Forms\Components\Select::make('instrument_id')
                                ->label('Instrument')
                                ->options(MusicInstrument::pluck('name', 'id'))
                                ->searchable()
                                ->required(false),

                            Forms\Components\Select::make('studio_id')
                                ->label('Studio')
                                ->options(MusicStudio::pluck('name', 'id'))
                                ->searchable()
                                ->required(false),

                            Forms\Components\Select::make('lesson_id')
                                ->label('Lesson')
                                ->options(MusicLesson::pluck('name', 'id'))
                                ->searchable()
                                ->required(false),

                            Forms\Components\Toggle::make('is_published')
                                ->label('Publicly Visible')
                                ->default(true),

                            Forms\Components\Toggle::make('is_verified_purchase')
                                ->label('Verified Purchase')
                                ->default(true),

                            Forms\Components\KeyValue::make('tags')
                                ->label('Meta Analysis')
                                ->required(false),
                        ])->columns(2),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('user.name')
                        ->label('Customer')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('rating')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->sortable()
                        ->label('Score'),

                    Tables\Columns\TextColumn::make('comment')
                        ->limit(50)
                        ->tooltip(fn ($state) => $state),

                    Tables\Columns\IconColumn::make('is_verified_purchase')
                        ->boolean()
                        ->label('Verified'),

                    Tables\Columns\IconColumn::make('is_published')
                        ->boolean()
                        ->label('Live'),

                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\TernaryFilter::make('is_published'),
                    Tables\Filters\TernaryFilter::make('is_verified_purchase'),
                    Tables\Filters\SelectFilter::make('rating')
                        ->options([
                            '1' => '1 Star',
                            '2' => '2 Stars',
                            '3' => '3 Stars',
                            '4' => '4 Stars',
                            '5' => '5 Stars',
                        ]),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ])
                ->emptyStateHeading('No reviews yet')
                ->emptyStateDescription('Reviews will appear here once customers provide feedback.');
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListMusicReviews::route('/'),
                'create' => Pages\CreateMusicReview::route('/create'),
                'edit' => Pages\EditMusicReview::route('/{record}/edit'),
            ];
        }

        /**
         * Apply Tenant Scoping for the table.
         */
        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])
                ->where('music_reviews.tenant_id', tenant()->id);
        }
}
