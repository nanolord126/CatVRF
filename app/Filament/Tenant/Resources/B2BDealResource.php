<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\RealEstate\Models\B2BDeal;
use App\Filament\Tenant\Resources\B2BDealResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\RealEstate\Services\AIPropertyMatcherService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

final class B2BDealResource extends Resource
{
    protected static ?string $model = B2BDeal::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Недвижимость';

    protected static ?string $label = 'Инвест-сделка (B2B)';

    protected static ?string $pluralLabel = 'Инвест-сделки (B2B)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Инвестиционное предложение')
                    ->description('Параметры сделки для инвесторов и юридических лиц')
                    ->schema([
                        Forms\Components\Select::make('listing_id')
                            ->relationship('listing', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Объявление'),
                        Forms\Components\Select::make('investor_id')
                            ->relationship('investor', 'name')
                            ->searchable()
                            ->label('Инвестор (Tenant)'),
                        Forms\Components\TextInput::make('offered_price')
                            ->numeric()
                            ->required()
                            ->label('Предложенная цена (коп.)')
                            ->suffix('коп.')
                            ->columnSpan(1),
                        Forms\Components\Select::make('status')
                            ->options([
                                'new' => 'Новая',
                                'proposal' => 'Предложение/Оффер',
                                'negotiation' => 'Переговоры',
                                'due_diligence' => 'Due Diligence',
                                'closed' => 'Закрыта/Подписана',
                                'rejected' => 'Отклонена',
                            ])
                            ->default('new')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Аналитический блок (AI)')
                    ->description('Расчет эффективности инвестиций')
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('ai_scoring')
                                ->label('AI Скоринг сделки')
                                ->icon('heroicon-o-cpu-chip')
                                ->color('success')
                                ->requiresConfirmation()
                                ->action(function ($record, $set) {
                                    if (!$record) return;
                                    
                                    try {
                                        $aiService = app(AIPropertyMatcherService::class);
                                        $potential = $aiService->calculateInvestmentPotential($record->listing->property);
                                        
                                        Notification::make()
                                            ->title('AI Анализ сделки завершен')
                                            ->body("ROI: {$potential['roi_percent']}%, CapRate: {$potential['cap_rate']}%.")
                                            ->success()
                                            ->send();
                                            
                                        $set('deal_terms.roi_forecast', $potential['roi_percent']);
                                        $set('deal_terms.payback_period', $potential['payback_years']);
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Ошибка AI анализа')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ]),
                        Forms\Components\KeyValue::make('deal_terms')
                            ->label('Условия и прогнозы')
                            ->placeholder('ROI_Forecast, Payback_Years, Discount_Requested')
                            ->addActionLabel('Добавить условие'),
                    ]),

                Forms\Components\Section::make('Метаданные и Скрытые параметры')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                           ->label('Технические метаданные сделки')
                           ->columnSpanFull(),
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
                    ->label('Объект/Оффер')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('investor.name')
                    ->label('Инвестор')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('offered_price')
                    ->label('Предложение')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Статус')
                    ->color(fn (string $state): string => match ($state) {
                        'closed' => 'success',
                        'new' => 'info',
                        'negotiation' => 'warning',
                        'rejected' => 'danger',
                        'due_diligence' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Дата'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'Новая',
                        'closed' => 'Закрыта',
                        'rejected' => 'Отклонена',
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
            'index' => Pages\ListB2BDeals::route('/'),
            'create' => Pages\CreateB2BDeal::route('/create'),
            'edit' => Pages\EditB2BDeal::route('/{record}/edit'),
        ];
    }
}
