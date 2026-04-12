<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class NftGiftResource extends Resource
{

    protected static ?string $model = NftGift::class;

        protected static ?string $navigationIcon = 'heroicon-o-star';
        protected static ?string $navigationLabel = 'NFT подарки';
        protected static ?string $pluralModelLabel = 'NFT подарки';
        protected static ?string $modelLabel = 'NFT подарок';

        protected static ?int $navigationSort = 3;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Информация о подарке')
                        ->schema([
                            TextInput::make('sender.name')
                                ->label('Отправитель')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('stream.blogger.display_name')
                                ->label('Блогер (получатель)')
                                ->disabled()
                                ->columnSpan(1),

                            Select::make('gift_type')
                                ->label('Тип подарка')
                                ->options([
                                    'bronze' => 'Бронза (500₽)',
                                    'silver' => 'Серебро (2500₽)',
                                    'gold' => 'Золото (5000₽)',
                                    'diamond' => 'Алмаз (10000₽)',
                                    'platinum' => 'Платина (25000₽)',
                                ])
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('amount')
                                ->label('Сумма (копейки)')
                                ->numeric()
                                ->disabled()
                                ->columnSpan(1),

                            Textarea::make('message')
                                ->label('Сообщение')
                                ->rows(2)
                                ->disabled()
                                ->columnSpan('full'),
                        ])->columns(2),

                    Section::make('Статус минтинга NFT')
                        ->schema([
                            BadgeColumn::make('minting_status')
                                ->label('Статус',)
                                ->colors([
                                    'gray' => 'pending',
                                    'info' => 'minting',
                                    'success' => 'minted',
                                    'danger' => 'failed',
                                ])
                                ->formatStateUsing(fn (string $state) => match($state) {
                                    'minting' => 'Минтится',
                                    'minted' => 'Отчеканено',
                                    'failed' => 'Ошибка',
                                })
                                ->columnSpan(1),

                            TextInput::make('minted_at')
                                ->label('Дата минтинга')
                                ->type('datetime-local')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('nft_address')
                                ->label('Адрес NFT (TON)')
                                ->disabled()
                                ->hint('Адрес смарт-контракта на TON блокчейне')
                                ->columnSpan('full'),

                            TextInput::make('nft_collection_address')
                                ->label('Адрес коллекции (TON)')
                                ->disabled()
                                ->columnSpan('full'),
                        ])->columns(2),

                    Section::make('Апгрейд до Collector NFT')
                        ->schema([
                            BadgeColumn::make('is_upgraded')
                                ->label('Апгрейдно')
                                ->colors([
                                    'gray' => false,
                                    'success' => true,
                                ])
                                ->formatStateUsing(fn (bool $state) => $state ? 'Да' : 'Нет')
                                ->columnSpan(1),

                            TextInput::make('upgrade_eligible_at')
                                ->label('Можно апгрейдить с')
                                ->type('datetime-local')
                                ->disabled()
                                ->hint('14 дней после минтинга')
                                ->columnSpan(1),

                            TextInput::make('upgraded_at')
                                ->label('Дата апгрейда')
                                ->type('datetime-local')
                                ->disabled()
                                ->columnSpan(1),
                        ])->columns(2),

                    Section::make('Метаданные')
                        ->schema([
                            Textarea::make('metadata')
                                ->label('Metadata (JSON)')
                                ->disabled()
                                ->rows(5)
                                ->columnSpan('full')
                                ->hint('IPFS метаданные NFT'),
                        ]),

                    Section::make('Модерация')
                        ->schema([
                            Select::make('moderation_status')
                                ->label('Статус модерации')
                                ->options([
                                    'approved' => 'Одобрен',
                                    'flagged' => 'Отмечен',
                                    'rejected' => 'Отклонен',
                                ])
                                ->columnSpan(1),

                            Textarea::make('moderation_notes')
                                ->label('Заметки модератора')
                                ->rows(2)
                                ->columnSpan('full'),
                        ])->columns(2),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListNftGift::route('/'),
                'create' => Pages\CreateNftGift::route('/create'),
                'edit' => Pages\EditNftGift::route('/{record}/edit'),
                'view' => Pages\ViewNftGift::route('/{record}'),
            ];
        }
}
