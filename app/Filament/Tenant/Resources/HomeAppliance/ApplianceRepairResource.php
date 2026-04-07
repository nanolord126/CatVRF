<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HomeAppliance;

use Filament\Resources\Resource;

final class ApplianceRepairResource extends Resource
{

    protected static ?string $model = ApplianceRepairOrder::class;
        protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
        protected static ?string $navigationGroup = 'Сервисное обслуживание';
        protected static ?string $modelLabel = 'Заказ на ремонт';
        protected static ?string $pluralModelLabel = 'Заказы на ремонт';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Group::make()->schema([
                        Forms\Components\Section::make('Информация об устройстве')
                            ->schema([
                                Forms\Components\Select::make('appliance_type')
                                    ->options([
                                        'washing_machine' => 'Стиральная машина',
                                        'fridge' => 'Холодильник',
                                        'ac' => 'Кондиционер'
                                    ])->required(),
                                Forms\Components\TextInput::make('brand_name')->required(),
                                Forms\Components\TextInput::make('model_number')->nullable(),
                                Forms\Components\Textarea::make('issue_description')->required()->columnSpanFull(),
                            ])->columns(2),
                        Forms\Components\Section::make('Локация и время')
                            ->schema([
                                Forms\Components\DateTimePicker::make('visit_scheduled_at')->label('Дата выезда'),
                                Forms\Components\KeyValue::make('address_json')->label('Адрес (JSON)')->required(),
                            ])
                    ])->columnSpan(['lg' => 2]),

                    Forms\Components\Group::make()->schema([
                        Forms\Components\Section::make('Статус и Финансы')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Новая',
                                        'diagnostic' => 'Диагностика',
                                        'in_repair' => 'В ремонте',
                                        'completed' => 'Завершен'
                                    ])->required(),
                                Forms\Components\Toggle::make('is_b2b')->label('B2B Клиент'),
                                Forms\Components\TextInput::make('labor_cost_kopecks')->numeric()->label('Работа (коп)'),
                                Forms\Components\TextInput::make('parts_cost_kopecks')->numeric()->label('Запчасти (коп)')->readOnly(),
                                Forms\Components\TextInput::make('total_cost_kopecks')->numeric()->label('Итого (коп)')->readOnly(),
                            ])
                    ])->columnSpan(['lg' => 1]),
                ])->columns(3);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('uuid')->label('ID')->searchable()->copyable(),
                    TextColumn::make('appliance_type')->label('Техника')->sortable(),
                    TextColumn::make('brand_name')->label('Бренд'),
                    BadgeColumn::make('status')
                        ->colors([
                            'primary' => 'pending',
                            'warning' => 'diagnostic',
                            'success' => 'completed',
                            'danger' => 'cancelled',
                        ]),
                    TextColumn::make('visit_scheduled_at')->label('Выезд')->dateTime()->sortable(),
                    TextColumn::make('total_cost_kopecks')->label('Сумма (коп)')->money('RUB', locale: 'ru'),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')->options([
                        'pending' => 'Новые',
                        'in_repair' => 'В работе',
                        'completed' => 'Завершено'
                    ])
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([
                    // Default tenant scope is handled via model booted()
                ]);
        }
}
