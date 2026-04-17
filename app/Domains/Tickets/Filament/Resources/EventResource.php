<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class EventResource extends Resource
{

    protected static ?string $model = Event::class;
        protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
        protected static ?string $navigationLabel = 'События';
        protected static ?int $navigationSort = 1;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Название')
                                ->required()
                    ]),

                Forms\Components\Section::make('Дата и место')
                    ->schema([
                        Forms\Components\DateTimePickerInput::make('starts_at')
                            ->label('Начало')
                            ->required(),
                        Forms\Components\DateTimePickerInput::make('ends_at')
                            ->label('Окончание')
                            ->required(),
                        Forms\Components\TextInput::make('venue_name')
                            ->label('Место проведения')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('venue_address')
                            ->label('Адрес')
                            ->required()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Параметры')
                    ->schema([
                        Forms\Components\TextInput::make('total_capacity')
                            ->label('Общая вместимость')
                            ->required()
                            ->numeric(),
                        Forms\Components\FileUpload::make('banner_url')
                            ->label('Баннер')
                            ->image(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
                                'published' => 'Опубликовано',
                                'ongoing' => 'Проходит',
                                'completed' => 'Завершено',
                                'cancelled' => 'Отменено',
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'published' => 'success',
                        'ongoing' => 'info',
                        'completed' => 'secondary',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Начало')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tickets_sold')
                    ->label('Билетов продано')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'published' => 'Опубликовано',
                        'ongoing' => 'Проходит',
                        'completed' => 'Завершено',
                        'cancelled' => 'Отменено',
                    ]),
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
            'index' => \App\Domains\Tickets\Filament\Resources\EventResource\Pages\ListEvents::route('/'),
            'create' => \App\Domains\Tickets\Filament\Resources\EventResource\Pages\CreateEvent::route('/create'),
            'edit' => \App\Domains\Tickets\Filament\Resources\EventResource\Pages\EditEvent::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()?->id)
            ->with(['organizer', 'ticketTypes', 'reviews']);
    }
}
