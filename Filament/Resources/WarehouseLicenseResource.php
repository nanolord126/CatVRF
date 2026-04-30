<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseLicenseResource\Pages;
use App\Models\WarehouseLicense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class WarehouseLicenseResource extends Resource
{
    protected static ?string $model = WarehouseLicense::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Warehouse Compliance';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('License Information')
                    ->schema([
                        Forms\Components\Select::make('warehouse_id')
                            ->relationship('warehouse', 'name')
                            ->required()
                            ->label('Warehouse'),
                        Forms\Components\Select::make('license_type')
                            ->options([
                                'pharmaceutical' => 'Pharmaceutical',
                                'narcotic' => 'Narcotic',
                                'psychotropic' => 'Psychotropic',
                                'controlled' => 'Controlled Substances',
                            ])
                            ->required()
                            ->label('License Type'),
                        Forms\Components\TextInput::make('license_number')
                            ->required()
                            ->unique()
                            ->label('License Number'),
                        Forms\Components\DatePicker::make('issue_date')
                            ->required()
                            ->label('Issue Date'),
                        Forms\Components\DatePicker::make('expiry_date')
                            ->required()
                            ->label('Expiry Date'),
                    ]),

                Forms\Components\Section::make('Authority')
                    ->schema([
                        Forms\Components\Textarea::make('issuing_authority')
                            ->required()
                            ->label('Issuing Authority'),
                        Forms\Components\Textarea::make('license_scope')
                            ->label('License Scope'),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'revoked' => 'Revoked',
                                'expired' => 'Expired',
                            ])
                            ->default('active')
                            ->required()
                            ->label('Status'),
                        Forms\Components\Toggle::make('has_temporary_restrictions')
                            ->label('Has Temporary Restrictions'),
                        Forms\Components\Textarea::make('suspension_reason')
                            ->visible(fn (Forms\Get $get) => in_array($get('status'), ['suspended', 'revoked']))
                            ->label('Suspension/Revocation Reason'),
                        Forms\Components\DatePicker::make('suspension_date')
                            ->visible(fn (Forms\Get $get) => in_array($get('status'), ['suspended', 'revoked']))
                            ->label('Suspension Date'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('license_number')
                    ->searchable()
                    ->sortable()
                    ->label('License Number'),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->searchable()
                    ->label('Warehouse'),
                Tables\Columns\BadgeColumn::make('license_type')
                    ->colors([
                        'primary' => 'pharmaceutical',
                        'danger' => 'narcotic',
                        'warning' => 'psychotropic',
                        'secondary' => 'controlled',
                    ])
                    ->label('Type'),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->label('Expiry Date'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'revoked',
                        'gray' => 'expired',
                    ])
                    ->label('Status'),
                Tables\Columns\TextColumn::make('days_until_expiry')
                    ->formatStateUsing(fn ($record) => $record->getDaysUntilExpiry() ?? 'N/A')
                    ->label('Days Until Expiry'),
                Tables\Columns\IconColumn::make('is_valid')
                    ->boolean()
                    ->label('Valid'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('license_type')
                    ->options([
                        'pharmaceutical' => 'Pharmaceutical',
                        'narcotic' => 'Narcotic',
                        'psychotropic' => 'Psychotropic',
                        'controlled' => 'Controlled Substances',
                    ])
                    ->label('License Type'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'revoked' => 'Revoked',
                        'expired' => 'Expired',
                    ])
                    ->label('Status'),
                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn ($query) => $query->expiringSoon(90))
                    ->label('Expiring Soon (90 days)'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouseLicenses::route('/'),
            'create' => Pages\CreateWarehouseLicense::route('/create'),
            'edit' => Pages\EditWarehouseLicense::route('/{record}/edit'),
        ];
    }
}
