<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources\ReturnPolicyResource\Pages;

use App\Domains\Supermarket\Filament\Resources\ReturnPolicyResource;
use App\Domains\Supermarket\Models\ReturnPolicy;
use Filament\Actions;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;

class ViewReturnPolicy extends ViewRecord
{
    protected static string $resource = ReturnPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Основные параметры')
                    ->schema([
                        TextEntry::make('vertical')
                            ->label('Вертикаль'),
                        TextEntry::make('sub_vertical')
                            ->label('Под-вертикаль')
                            ->formatStateUsing(fn ($state): string => match ($state) {
                                'meat_shops' => 'Мясные лавки',
                                'vegan_products' => 'Веган-продукты',
                                'confectionery' => 'Кондитерка',
                                'farm_direct' => 'Фермерские продукты',
                                'grocery_and_delivery' => 'Бакалея',
                                'household' => 'Товары для дома',
                                'food' => 'Готовая еда',
                                'bakery' => 'Выпечка',
                                'dairy' => 'Молочные продукты',
                                null => 'Общая политика',
                                default => $state,
                            })
                            ->default('-'),
                        TextEntry::make('customer_type')
                            ->label('Тип клиента')
                            ->badge(),
                        TextEntry::make('max_days')
                            ->label('Максимальное количество дней'),
                        TextEntry::make('priority')
                            ->label('Приоритет'),
                    ])
                    ->columns(3),

                Section::make('Разрешённые причины')
                    ->schema([
                        KeyValueEntry::make('allowed_reasons')
                            ->label('Причины')
                            ->keyLabel('Код')
                            ->valueLabel('Название')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Требования к доказательствам')
                    ->schema([
                        IconEntry::make('cold_chain_only_defect')
                            ->label('Только при браке для холодной цепи')
                            ->boolean(),
                        IconEntry::make('requires_photo')
                            ->label('Требуются фото доказательства')
                            ->boolean(),
                        IconEntry::make('requires_temperature')
                            ->label('Требуется проверка температуры')
                            ->boolean(),
                        IconEntry::make('requires_receipt')
                            ->label('Обязателен чек')
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make('Лимиты возврата')
                    ->schema([
                        TextEntry::make('max_refund_percent')
                            ->label('Максимальный процент возврата')
                            ->suffix('%'),
                        TextEntry::make('max_refund_amount')
                            ->label('Максимальная сумма возврата')
                            ->money('RUB')
                            ->default('Безлимитно'),
                    ])
                    ->columns(2),

                Section::make('Период действия')
                    ->schema([
                        TextEntry::make('valid_from')
                            ->label('Действует с')
                            ->dateTime()
                            ->default('Не указано'),
                        TextEntry::make('valid_until')
                            ->label('Действует до')
                            ->dateTime()
                            ->default('Не указано'),
                        IconEntry::make('is_active')
                            ->label('Активна')
                            ->boolean(),
                    ])
                    ->columns(3),

                Section::make('Дополнительно')
                    ->schema([
                        TextEntry::make('description')
                            ->label('Описание')
                            ->default('-'),
                        TextEntry::make('created_at')
                            ->label('Создана')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Обновлена')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Section::make('Описание для пользователя')
                    ->schema([
                        TextEntry::make('user_description')
                            ->label('Текст политики для клиента')
                            ->default('-')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public function getUserDescription(): string
    {
        return $this->record->getUserDescription();
    }
}
