<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources;

use App\Domains\Auto\Filament\Resources\AIDiagnosticsHistoryResource\Pages;
use App\Domains\Auto\Filament\Resources\AIDiagnosticsHistoryResource\Pages\ListDiagnosticsHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class AIDiagnosticsHistoryResource extends Resource
{
    protected static ?string $model = \App\Domains\Auto\Models\AutoDiagnosticsHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'AI Diagnostics History';

    protected static ?string $modelLabel = 'Diagnostics Record';

    protected static ?string $navigationGroup = 'Auto';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vehicle Information')
                    ->schema([
                        Forms\Components\TextInput::make('vehicle.vin')
                            ->label('VIN')
                            ->disabled(),
                        Forms\Components\TextInput::make('vehicle.make')
                            ->label('Make')
                            ->disabled(),
                        Forms\Components\TextInput::make('vehicle.model')
                            ->label('Model')
                            ->disabled(),
                        Forms\Components\TextInput::make('vehicle.year')
                            ->label('Year')
                            ->disabled(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Damage Detection')
                    ->schema([
                        Forms\Components\KeyValue::make('diagnostics_data.damage_detection')
                            ->label('Damage Details')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Price Estimate')
                    ->schema([
                        Forms\Components\TextInput::make('diagnostics_data.price_estimate.total')
                            ->label('Total Price (RUB)')
                            ->disabled()
                            ->numeric(),
                        Forms\Components\TextInput::make('diagnostics_data.price_estimate.labor_total')
                            ->label('Labor Total (RUB)')
                            ->disabled()
                            ->numeric(),
                        Forms\Components\TextInput::make('diagnostics_data.price_estimate.parts_total')
                            ->label('Parts Total (RUB)')
                            ->disabled()
                            ->numeric(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\TextInput::make('correlation_id')
                            ->label('Correlation ID')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Created At')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.vin')
                    ->label('VIN')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.make')
                    ->label('Make')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.model')
                    ->label('Model')
                    ->sortable(),
                Tables\Columns\TextColumn::make('diagnostics_data.damage_detection.total_count')
                    ->label('Damages')
                    ->sortable(),
                Tables\Columns\TextColumn::make('diagnostics_data.damage_detection.critical_count')
                    ->label('Critical')
                    ->sortable()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('diagnostics_data.price_estimate.total')
                    ->label('Total (RUB)')
                    ->money('rub')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('critical_damages')
                    ->query(fn (Builder $query): Builder => $query->whereJsonContains('diagnostics_data->damage_detection->critical_count', 0, '>', ))
                    ->label('Has Critical Damages'),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->label('Date Range'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            'vehicle' => Tables\Columns\TextColumn::make('vehicle'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiagnosticsHistory::route('/'),
            'view' => Pages\ViewDiagnosticsHistory::route('/{record}'),
        ];
    }
}
