<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

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
                        ]),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListListing::route('/'),
                'create' => Pages\CreateListing::route('/create'),
                'edit' => Pages\EditListing::route('/{record}/edit'),
                'view' => Pages\ViewListing::route('/{record}'),
            ];
        }
}
