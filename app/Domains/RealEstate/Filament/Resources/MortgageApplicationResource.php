<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class MortgageApplicationResource extends Resource
{

    protected static ?string $model = MortgageApplication::class;

        protected static ?string $navigationIcon = 'heroicon-o-document-text';

        protected static ?string $navigationGroup = 'Real Estate';

        protected static ?string $label = 'Ипотека';

        protected static ?string $pluralLabel = 'Заявки на ипотеку';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Данные заявки')
                        ->schema([
                            TextInput::make('property_price')
                                ->label('Стоимость объекта (₽)')
                                ->numeric()
                                ->required(),
                            TextInput::make('loan_amount')
                                ->label('Сумма кредита (₽)')
                                ->numeric()
                                ->required(),
                            TextInput::make('initial_payment')
                                ->label('Первоначальный взнос (₽)')
                                ->numeric()
                                ->required(),
                            TextInput::make('loan_term_months')
                                ->label('Срок кредита (месяцы)')
                                ->numeric()
                                ->required(),
                            TextInput::make('interest_rate')
                                ->label('Процентная ставка')
                                ->numeric()
                                ->required(),
                            Select::make('bank')
                                ->label('Банк')
                                ->options([
                                    'sberbank' => 'Сбербанк',
                                    'vtb' => 'ВТБ',
                                    'gazprombank' => 'Газпромбанк',
                                    'other' => 'Другой',
                                ])->required(),
                            Select::make('status')
                                ->label('Статус')
                                ->options([
                                    'draft' => 'Черновик',
                                    'submitted' => 'Подана',
                                    'approved' => 'Одобрена',
                                    'rejected' => 'Отклонена',
                                    'completed' => 'Завершена',
                                ]),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('property.address')
                        ->label('Объект')
                        ->searchable(),
                    TextColumn::make('loan_amount')
                        ->label('Размер кредита')
                        ->money('RUB', 100)
                        ->sortable(),
                    TextColumn::make('bank')
                        ->label('Банк')
                        ->sortable(),
                    TextColumn::make('interest_rate')
                        ->label('Ставка')
                        ->suffix('%'),
                    TextColumn::make('status')->badge()
                        ->label('Статус')
                        ->colors([
                            'info' => 'draft',
                            'warning' => 'submitted',
                            'success' => 'approved',
                            'danger' => 'rejected',
                            'secondary' => 'completed',
                        ]),
                    TextColumn::make('created_at')
                        ->label('Создано')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    SelectFilter::make('bank')
                        ->label('Банк')
                        ->options([
                            'sberbank' => 'Сбербанк',
                            'vtb' => 'ВТБ',
                            'gazprombank' => 'Газпромбанк',
                            'other' => 'Другой',
                        ]),
                    SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'draft' => 'Черновик',
                            'submitted' => 'Подана',
                            'approved' => 'Одобрена',
                            'rejected' => 'Отклонена',
                        ]),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\RealEstate\Filament\Resources\MortgageApplicationResource\Pages\ListMortgageApplications::route('/'),
            ];
        }
}
