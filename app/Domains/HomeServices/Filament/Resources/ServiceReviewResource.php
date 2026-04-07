<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources;

use Filament\Resources\Resource;

final class ServiceReviewResource extends Resource
{

    protected static ?string $model = ServiceReview::class;
        protected static ?string $navigationIcon = 'heroicon-o-star';
        protected static ?string $navigationLabel = 'Отзывы';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Отзыв')
                    ->schema([
                        Forms\Components\Select::make('contractor_id')->label('Подрядчик')->relationship('contractor', 'company_name')->required(),
                        Forms\Components\Select::make('reviewer_id')->label('Автор')->relationship('reviewer', 'email')->required(),
                        Forms\Components\Select::make('job_id')->label('Заказ')->relationship('job', 'id')->nullable(),
                    ]),
                Forms\Components\Section::make('Содержание')
                    ->schema([
                        Forms\Components\Slider::make('rating')->label('Рейтинг')->min(1)->max(5)->required(),
                        Forms\Components\TextInput::make('title')->label('Заголовок')->required(),
                        Forms\Components\RichEditor::make('content')->label('Текст')->required(),
                    ]),
                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Toggle::make('verified_job')->label('Проверённый заказ'),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('contractor.company_name')->label('Подрядчик')->searchable(),
                    Tables\Columns\TextColumn::make('reviewer.email')->label('Автор'),
                    Tables\Columns\TextColumn::make('rating')->label('Рейтинг')->formatStateUsing(fn($state) => "⭐ $state"),
                    Tables\Columns\IconColumn::make('verified_job')->label('Проверено')->boolean(),
                    Tables\Columns\TextColumn::make('published_at')->label('Дата')->dateTime(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('rating')->options([
                        1 => '1 звезда',
                        2 => '2 звезды',
                        3 => '3 звезды',
                        4 => '4 звезды',
                        5 => '5 звёзд',
                    ]),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\HomeServices\Filament\Resources\ServiceReviewResource\Pages\ListServiceReviews::route('/'),
                'create' => \App\Domains\HomeServices\Filament\Resources\ServiceReviewResource\Pages\CreateServiceReview::route('/create'),
                'edit' => \App\Domains\HomeServices\Filament\Resources\ServiceReviewResource\Pages\EditServiceReview::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant()->id);
        }
}
