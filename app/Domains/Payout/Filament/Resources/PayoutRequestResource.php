<?php declare(strict_types=1);

namespace App\Domains\Payout\Filament\Resources;

use App\Domains\Payout\Models\PayoutRequest;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class PayoutRequestResource extends Resource
{
    protected static ?string $model = PayoutRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-yen';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('rub'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('cancellation_reason')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('cancel')
                    ->requiresConfirmation()
                    ->action(fn (PayoutRequest $record) => $record->update(['status' => 'cancelled']))
                    ->visible(fn (PayoutRequest $record) => $record->canBeCancelled()),
            ]);
    }
}
