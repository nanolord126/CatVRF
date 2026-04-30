<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources;

use App\Domains\Payment\Filament\Resources\PayoutBatchResource\Pages;
use App\Domains\Payment\Models\PayoutBatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutBatchResource extends Resource
{
    protected static ?string $model = PayoutBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Batch Information')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'partial' => 'Partial',
                            ])
                            ->required(),
                        Forms\Components\Select::make('provider')
                            ->options([
                                'tinkoff' => 'Tinkoff',
                                'tochka' => 'Tochka',
                                'sber' => 'Sber',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('total_amount_kopecks')
                            ->label('Total Amount (kopecks)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('total_count')
                            ->label('Total Count')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('processed_count')
                            ->label('Processed Count')
                            ->numeric(),
                        Forms\Components\TextInput::make('failed_count')
                            ->label('Failed Count')
                            ->numeric(),
                        Forms\Components\TextInput::make('provider_batch_id')
                            ->label('Provider Batch ID')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Scheduled At'),
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Started At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completed At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('failed_at')
                            ->label('Failed At')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'info',
                        'pending' => 'gray',
                        'failed' => 'danger',
                        'partial' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount_kopecks')
                    ->label('Total Amount (kopecks)')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2)),
                Tables\Columns\TextColumn::make('total_count')
                    ->label('Total Count')
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_count')
                    ->label('Processed')
                    ->sortable(),
                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Failed')
                    ->sortable()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'partial' => 'Partial',
                    ]),
                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'tinkoff' => 'Tinkoff',
                        'tochka' => 'Tochka',
                        'sber' => 'Sber',
                    ]),
                Tables\Filters\Filter::make('ready_to_process')
                    ->label('Ready to Process')
                    ->query(fn ($query) => $query->where('status', 'pending')->where('scheduled_at', '<=', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('process')
                    ->label('Process')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (PayoutBatch $record) => $record->update(['status' => 'processing']))
                    ->visible(fn (PayoutBatch $record): bool => $record->isReadyToProcess()),
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
            'index' => Pages\ListPayoutBatches::route('/'),
            'create' => Pages\CreatePayoutBatch::route('/create'),
            'view' => Pages\ViewPayoutBatch::route('/{record}'),
            'edit' => Pages\EditPayoutBatch::route('/{record}/edit'),
        ];
    }
}
