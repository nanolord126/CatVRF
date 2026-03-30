<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReviewResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Review::class;

        protected static ?string $navigationIcon = 'heroicon-o-star';
        protected static ?string $navigationGroup = 'Beauty & Wellness';
        protected static ?string $navigationLabel = 'Отзывы';
        protected static ?string $modelLabel = 'отзыв';
        protected static ?string $pluralModelLabel = 'отзывы';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Детали отзыва')
                    ->schema([
                        Forms\Components\Select::make('salon_id')
                            ->relationship('salon', 'name', fn(Builder $query) => $query->where('tenant_id', tenant('id')))
                            ->required()
                            ->label('Салон'),
                        Forms\Components\Select::make('master_id')
                            ->relationship('master', 'full_name', fn(Builder $query) => $query->where('tenant_id', tenant('id')))
                            ->required()
                            ->label('Мастер'),
                        Forms\Components\Select::make('appointment_id')
                            ->relationship('appointment', 'id', fn(Builder $query) => $query->where('tenant_id', tenant('id')))
                            ->searchable()
                            ->required()
                            ->label('Запись'),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->label('Клиент'),
                        Forms\Components\TextInput::make('rating')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->required()
                            ->label('Рейтинг (1-5)'),
                        Forms\Components\Textarea::make('comment')
                            ->columnSpanFull()
                            ->label('Комментарий'),
                        Forms\Components\FileUpload::make('photos')
                            ->multiple()
                            ->image()
                            ->directory('review-photos')
                            ->label('Фотографии'),
                    ])->columns(2),

                Forms\Components\Hidden::make('uuid')->default(fn () => (string) Str::uuid())->dehydrated(),
                Forms\Components\Hidden::make('tenant_id')->default(fn () => tenant('id')),
                Forms\Components\Hidden::make('correlation_id')->default(fn () => (string) Str::uuid()),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                Tables\Columns\TextColumn::make('appointment.id')->label('ID Записи')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Клиент')->searchable(),
                Tables\Columns\TextColumn::make('master.full_name')->label('Мастер')->searchable(),
                Tables\Columns\TextColumn::make('rating')->sortable()->label('Рейтинг'),
                Tables\Columns\TextColumn::make('comment')->limit(50)->label('Комментарий'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->label('Дата'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('master_id')
                    ->relationship('master', 'full_name', fn(Builder $query) => $query->where('tenant_id', tenant('id')))
                    ->label('Мастер'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->action(function ($record) {
                    DB::transaction(function () use ($record) {
                        $correlationId = (string) Str::uuid();
                        app(FraudControlService::class)->check(Auth::id(), 'delete-review', 0, $correlationId);
                        Log::channel('audit')->info('Review deleted', ['record_id' => $record->id, 'tenant_id' => tenant('id'), 'correlation_id' => $correlationId]);
                        $record->delete();
                    });
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->action(function ($records) {
                        DB::transaction(function () use ($records) {
                            $correlationId = (string) Str::uuid();
                            app(FraudControlService::class)->check(Auth::id(), 'bulk-delete-reviews', 0, $correlationId);
                            Log::channel('audit')->info('Reviews bulk deleted', ['record_ids' => $records->pluck('id')->toArray(), 'tenant_id' => tenant('id'), 'correlation_id' => $correlationId]);
                            $records->each->delete();
                        });
                    }),
                ]),
            ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListReviews::route('/'),
                'create' => Pages\CreateReview::route('/create'),
                'edit' => Pages\EditReview::route('/{record}/edit'),
            ];
        }
}
