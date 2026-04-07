<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyReview as Review;
use App\Services\FraudControlService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Filament Resource: Отзывы (Beauty Reviews).
 *
 * Tenant-scoped через getEloquentQuery().
 * Сервисы резолвятся через app() — constructor injection в Resource не поддерживается.
 * Нет Facades, нет статических вызовов.
 *
 * @package App\Filament\Tenant\Resources\Beauty
 */
final class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon       = 'heroicon-o-star';
    protected static ?string $navigationGroup      = 'Beauty & Wellness';
    protected static ?string $navigationLabel      = 'Отзывы';
    protected static ?string $modelLabel           = 'отзыв';
    protected static ?string $pluralModelLabel     = 'отзывы';

    public static function form(Form $form): Form
    {
        $tenantId = filament()->getTenant()?->id;

        return $form->schema([
            Forms\Components\Section::make('Детали отзыва')
                ->schema([
                    Forms\Components\Select::make('salon_id')
                        ->relationship('salon', 'name', fn (Builder $query) => $query->where('tenant_id', $tenantId))
                        ->required()
                        ->label('Салон'),
                    Forms\Components\Select::make('master_id')
                        ->relationship('master', 'full_name', fn (Builder $query) => $query->where('tenant_id', $tenantId))
                        ->required()
                        ->label('Мастер'),
                    Forms\Components\Select::make('appointment_id')
                        ->relationship('appointment', 'id', fn (Builder $query) => $query->where('tenant_id', $tenantId))
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

            Forms\Components\Hidden::make('uuid')
                ->default(fn () => (string) Str::uuid())
                ->dehydrated(),
            Forms\Components\Hidden::make('tenant_id')
                ->default(fn () => filament()->getTenant()?->id),
            Forms\Components\Hidden::make('correlation_id')
                ->default(fn () => (string) Str::uuid()),
        ]);
    }

    public static function table(Table $table): Table
    {
        $tenantId = filament()->getTenant()?->id;

        return $table->columns([
            Tables\Columns\TextColumn::make('appointment.id')
                ->label('ID Записи')
                ->searchable(),
            Tables\Columns\TextColumn::make('user.name')
                ->label('Клиент')
                ->searchable(),
            Tables\Columns\TextColumn::make('master.full_name')
                ->label('Мастер')
                ->searchable(),
            Tables\Columns\TextColumn::make('rating')
                ->sortable()
                ->label('Рейтинг'),
            Tables\Columns\TextColumn::make('comment')
                ->limit(50)
                ->label('Комментарий'),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->label('Дата'),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('master_id')
                ->relationship('master', 'full_name', fn (Builder $query) => $query->where('tenant_id', $tenantId))
                ->label('Мастер'),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->action(function ($record): void {
                    $db = app(DatabaseManager::class);
                    $logger = app(LoggerInterface::class);

                    $db->transaction(function () use ($record, $logger): void {
                        $correlationId = (string) Str::uuid();

                        app(FraudControlService::class)->check(
                            userId: filament()->auth()->id() ?? 0,
                            operationType: 'delete-review',
                            amount: 0,
                            correlationId: $correlationId,
                        );

                        $logger->info('Review deleted', [
                            'record_id'      => $record->id,
                            'tenant_id'      => filament()->getTenant()?->id,
                            'correlation_id' => $correlationId,
                        ]);

                        $record->delete();
                    });
                }),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->action(function ($records): void {
                        $db = app(DatabaseManager::class);
                        $logger = app(LoggerInterface::class);

                        $db->transaction(function () use ($records, $logger): void {
                            $correlationId = (string) Str::uuid();

                            app(FraudControlService::class)->check(
                                userId: filament()->auth()->id() ?? 0,
                                operationType: 'bulk-delete-reviews',
                                amount: 0,
                                correlationId: $correlationId,
                            );

                            $logger->info('Reviews bulk deleted', [
                                'record_ids'     => $records->pluck('id')->toArray(),
                                'tenant_id'      => filament()->getTenant()?->id,
                                'correlation_id' => $correlationId,
                            ]);

                            $records->each->delete();
                        });
                    }),
            ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()?->id);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit'   => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
