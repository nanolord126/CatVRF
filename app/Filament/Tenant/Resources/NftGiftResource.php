<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Bloggers\Models\NftGift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;

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
                                'pending' => 'На ожидании',
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sender.name')
                    ->label('Отправитель')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stream.blogger.display_name')
                    ->label('Блогер')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('gift_type')
                    ->label('Тип подарка')
                    ->colors([
                        'gray' => 'bronze',
                        'info' => 'silver',
                        'warning' => 'gold',
                        'success' => 'diamond',
                        'danger' => 'platinum',
                    ])
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'bronze' => 'Бронза',
                        'silver' => 'Серебро',
                        'gold' => 'Золото',
                        'diamond' => 'Алмаз',
                        'platinum' => 'Платина',
                    })
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state) => '₽' . ($state / 100))
                    ->sortable(),

                BadgeColumn::make('minting_status')
                    ->label('Минтинг')
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'minting',
                        'success' => 'minted',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'pending' => 'На ожидании',
                        'minting' => 'Минтится',
                        'minted' => 'Отчеканено',
                        'failed' => 'Ошибка',
                    })
                    ->sortable(),

                BadgeColumn::make('is_upgraded')
                    ->label('Апгрейдно')
                    ->colors([
                        'gray' => false,
                        'success' => true,
                    ])
                    ->formatStateUsing(fn (bool $state) => $state ? '✓' : '✗')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gift_type')
                    ->label('Тип подарка')
                    ->options([
                        'bronze' => 'Бронза',
                        'silver' => 'Серебро',
                        'gold' => 'Золото',
                        'diamond' => 'Алмаз',
                        'platinum' => 'Платина',
                    ]),

                Tables\Filters\SelectFilter::make('minting_status')
                    ->label('Статус минтинга')
                    ->options([
                        'pending' => 'На ожидании',
                        'minting' => 'Минтится',
                        'minted' => 'Отчеканено',
                        'failed' => 'Ошибка',
                    ]),

                Tables\Filters\TernaryFilter::make('is_upgraded')
                    ->label('Апгрейдно'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (NftGift $record) => $record->minting_status === 'failed'),
                Tables\Actions\Action::make('retry_mint')
                    ->label('Повторить минтинг')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (NftGift $record) {
                        $record->update(['minting_status' => 'pending']);
                        \App\Domains\Bloggers\Jobs\MintNftGiftJob::dispatch($record);
                    })
                    ->visible(fn (NftGift $record) => $record->minting_status === 'failed'),
                Tables\Actions\Action::make('view_nft')
                    ->label('Просмотр NFT')
                    ->icon('heroicon-o-link')
                    ->url(function (NftGift $record) {
                        return "https://testnet.tonscan.org/address/{$record->nft_address}";
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (NftGift $record) => !empty($record->nft_address)),
                Tables\Actions\Action::make('flag')
                    ->label('Отметить')
                    ->icon('heroicon-o-flag')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('moderation_notes')
                            ->label('Причина')
                            ->required(),
                    ])
                    ->action(function (NftGift $record, array $data) {
                        $record->update([
                            'moderation_status' => 'flagged',
                            'moderation_notes' => $data['moderation_notes'],
                        ]);
                    }),
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
            'index' => \App\Filament\Tenant\Resources\NftGiftResource\Pages\ListNftGifts::route('/'),
            'create' => \App\Filament\Tenant\Resources\NftGiftResource\Pages\CreateNftGift::route('/create'),
            'view' => \App\Filament\Tenant\Resources\NftGiftResource\Pages\ViewNftGift::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\NftGiftResource\Pages\EditNftGift::route('/{record}/edit'),
        ];
    }
}
