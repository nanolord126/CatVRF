<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\RealEstate\Models\Listing;
use App\Filament\Tenant\Resources\ListingResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\RealEstate\Services\AIPropertyMatcherService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

final class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Недвижимость';

    protected static ?string $label = 'Объявление';

    protected static ?string $pluralLabel = 'Объявления';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Общая информация')
                    ->description('Детализированное описание рекламного предложения')
                    ->schema([
                        Forms\Components\Select::make('property_id')
                            ->relationship('property', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Объект недвижимости'),
                        Forms\Components\Select::make('type')
                            ->required()
                            ->label('Тип сделки')
                            ->options([
                                'sale' => 'Продажа',
                                'rent' => 'Аренда',
                                'lease_hold' => 'Переуступка (Leasehold)',
                                'ready_business' => 'Продажа готового бизнеса',
                            ])
                            ->reactive(),
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->label('Цена (в копейках)')
                            ->helperText('Сумма в минимальных единицах валюты')
                            ->suffix('коп.')
                            ->columnSpan(1),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Активно',
                                'archived' => 'Архив',
                                'moderation' => 'На модерации',
                                'sold' => 'Продано/Снято',
                            ])
                            ->default('active')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Маркетинг и Аналитика')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->label('Заголовок объявления'),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->label('Описание для клиентов')
                            ->rows(5),
                    ]),

                Forms\Components\Fieldset::make('AI Оценка')
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('calculate_roi')
                                ->label('Рассчитать Инвест-потенциал (AI)')
                                ->icon('heroicon-o-chart-bar')
                                ->color('success')
                                ->action(function ($record, $set) {
                                    if (!$record) return;
                                    
                                    try {
                                        $aiService = app(AIPropertyMatcherService::class);
                                        $potential = $aiService->calculateInvestmentPotential($record->property);
                                        
                                        Notification::make()
                                            ->title('AI Анализ завершен')
                                            ->body("ROI: {$potential['roi_percent']}%, Окупаемость: {$potential['payback_years']} лет")
                                            ->success()
                                            ->send();
                                            
                                        $set('metadata.ai_roi', $potential['roi_percent']);
                                        $set('metadata.cap_rate', $potential['cap_rate']);
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Ошибка AI анализа')
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ]),
                        Forms\Components\KeyValue::make('metadata')
                           ->label('Метаданные (AI / Аналитика)')
                           ->addActionLabel('Добавить поле'),
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
                Tables\Columns\TextColumn::make('property.name')
                    ->label('Объект')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Тип сделки')
                    ->color(fn (string $state): string => match ($state) {
                        'sale' => 'success',
                        'rent' => 'info',
                        'ready_business' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Статус')
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'moderation' => 'warning',
                        'archived' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Дата создания'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'sale' => 'Продажа',
                        'rent' => 'Аренда',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Активно',
                        'archived' => 'В архиве',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('archive')
                    ->label('В архив')
                    ->icon('heroicon-o-archive-box')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['status' => 'archived']))
                    ->visible(fn ($record) => $record->status !== 'archived'),
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
            'index' => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }
}
