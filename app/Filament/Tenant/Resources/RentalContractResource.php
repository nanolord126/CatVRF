<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\RealEstate\Models\RentalContract;
use App\Filament\Tenant\Resources\RentalContractResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\RealEstate\Services\RealEstateService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class RentalContractResource extends Resource
{
    protected static ?string $model = RentalContract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Недвижимость';

    protected static ?string $label = 'Договор аренды';

    protected static ?string $pluralLabel = 'Договоры аренды';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Детали контракта')
                    ->description('Юридические и финансовые условия аренды')
                    ->schema([
                        Forms\Components\Select::make('listing_id')
                            ->relationship('listing', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Объявление')
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $listing = \App\Domains\RealEstate\Models\Listing::find($state);
                                    if ($listing) {
                                        $set('rent_amount', $listing->price);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable()
                            ->label('Арендатор'),
                        Forms\Components\TextInput::make('rent_amount')
                            ->numeric()
                            ->required()
                            ->label('Сумма аренды (коп.)')
                            ->suffix('коп.')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('deposit_amount')
                            ->numeric()
                            ->label('Депозит (коп.)')
                            ->suffix('коп.')
                            ->columnSpan(1),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->required()
                                    ->label('Начало')
                                    ->default(now()),
                                Forms\Components\DatePicker::make('end_date')
                                    ->required()
                                    ->label('Окончание')
                                    ->afterOrEqual('start_date'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Черновик/Ожидает',
                                        'active' => 'Действует',
                                        'completed' => 'Завершен',
                                        'terminated' => 'Расторгнут',
                                        'disputed' => 'Спор/Арбитраж',
                                    ])
                                    ->default('pending')
                                    ->required(),
                            ]),
                    ])->columns(2),

                Forms\Components\Section::make('Правила и Метаданные')
                    ->schema([
                        Forms\Components\KeyValue::make('rules')
                            ->label('Особые условия и правила')
                            ->placeholder('Количество жильцов, Животные, Депозит возвращается...')
                            ->addActionLabel('Добавить условие'),
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Технические метаданные')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Действия')
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('sign_contract')
                                ->label('Подписать договор (Service)')
                                ->icon('heroicon-o-pencil-square')
                                ->color('success')
                                ->requiresConfirmation()
                                ->action(function ($record) {
                                    if (!$record) return;
                                    
                                    try {
                                        $service = app(RealEstateService::class);
                                        $service->signRentalContract($record->uuid, auth()->id());
                                        
                                        Notification::make()
                                            ->title('Договор подписан')
                                            ->success()
                                            ->send();
                                            
                                        $record->refresh();
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Ошибка подписания')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->visible(fn ($record) => $record && $record->status === 'pending'),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('listing.title')
                    ->label('Объект/Объявление')
                    ->sortable()
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Арендатор')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('rent_amount')
                    ->label('Аренда')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Статус')
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'completed' => 'info',
                        'terminated' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->label('Старт'),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->label('Финиш'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Активен',
                        'pending' => 'Ожидает',
                        'completed' => 'Завершен',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRentalContracts::route('/'),
            'create' => Pages\CreateRentalContract::route('/create'),
            'edit' => Pages\EditRentalContract::route('/{record}/edit'),
        ];
    }
}
