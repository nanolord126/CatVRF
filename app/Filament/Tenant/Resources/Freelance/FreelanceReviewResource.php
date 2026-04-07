<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance;

use Filament\Resources\Resource;

final class FreelanceReviewResource extends Resource
{

    protected static ?string $model = FreelanceReview::class;

        protected static ?string $navigationIcon = 'heroicon-o-star';

        protected static ?string $navigationGroup = 'Фриланс Биржа';

        protected static ?string $label = 'Отзыв';

        protected static ?string $pluralLabel = 'Отзывы';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Детали отзыва')
                        ->columns(2)
                        ->schema([
                            Select::make('order_id')
                                ->label('Заказ')
                                ->relationship('order', 'title')
                                ->searchable()
                                ->required(),

                            Select::make('client_id')
                                ->label('Автор (Клиент)')
                                ->relationship('client', 'name')
                                ->required(),

                            Select::make('freelancer_id')
                                ->label('Получатель (Фрилансер)')
                                ->relationship('freelancer', 'full_name')
                                ->required(),

                            TextInput::make('rating')
                                ->label('Оценка (1-5)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(5)
                                ->required(),

                            Textarea::make('comment')
                                ->label('Текст отзыва')
                                ->required()
                                ->columnSpanFull(),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('freelancer.full_name')
                        ->label('Фрилансер')
                        ->searchable(),

                    TextColumn::make('rating')
                        ->label('Оценка')
                        ->badge()
                        ->color(fn (int $state): string => match (true) {
                            $state >= 4 => 'success',
                            $state >= 3 => 'warning',
                            default => 'danger',
                        }),

                    TextColumn::make('comment')
                        ->label('Текст')
                        ->limit(50),

                    TextColumn::make('client.name')
                        ->label('Автор'),

                    TextColumn::make('created_at')
                        ->label('Дата')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('rating')
                        ->options([
                            '5' => '5 звезд',
                            '4' => '4 звезды',
                            '3' => '3 звезды',
                            '2' => '2 звезды',
                            '1' => '1 звезда',
                        ]),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListFreelanceReviews::route('/'),
                'create' => Pages\CreateFreelanceReview::route('/create'),
                'edit' => Pages\EditFreelanceReview::route('/{record}/edit'),
            ];
        }
}
