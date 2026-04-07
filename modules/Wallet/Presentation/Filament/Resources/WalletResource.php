<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Wallet\Infrastructure\Models\WalletModel;
use Modules\Wallet\Infrastructure\Models\WalletTransactionModel;

/**
 * Filament Resource: просмотр кошельков и транзакций (только чтение).
 */
final class WalletResource extends Resource
{
    protected static ?string $model           = WalletModel::class;
    protected static ?string $navigationIcon  = 'heroicon-o-wallet';
    protected static ?string $navigationLabel = 'Кошельки';
    protected static ?string $navigationGroup = 'Финансы';
    protected static ?int    $navigationSort  = 20;

    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    // ──────────────────────────────────────────────
    //  Table
    // ──────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('holder_id')
                    ->label('Пользователь ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Баланс (коп.)')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2) . ' ₽'),

                Tables\Columns\TextColumn::make('meta.hold_amount')
                    ->label('Холд (коп.)')
                    ->default(0)
                    ->formatStateUsing(fn ($state): string => number_format((int) $state / 100, 2) . ' ₽'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    // ──────────────────────────────────────────────
    //  Form (View only)
    // ──────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    // ──────────────────────────────────────────────
    //  Pages
    // ──────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Wallet\Presentation\Filament\Resources\WalletResource\Pages\ListWallets::route('/'),
            'view'  => \Modules\Wallet\Presentation\Filament\Resources\WalletResource\Pages\ViewWallet::route('/{record}'),
        ];
    }

    // ──────────────────────────────────────────────
    //  Tenant scoping
    // ──────────────────────────────────────────────

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('holder_type', 'App\\Models\\User')
            ->with([]);
    }
}
