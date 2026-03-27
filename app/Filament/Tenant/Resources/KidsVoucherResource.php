<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Education\Kids\Models\KidsVoucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

/**
 * KidsVoucherResource - Admin UI for Gift Cards, Trial Passes and Service Coupons.
 * Requirement: Form >= 60 lines.
 * Layer: Filament Resources (5/9)
 */
final class KidsVoucherResource extends Resource
{
    protected static ?string $model = KidsVoucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Baby & Kids';
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Voucher Identity')
                            ->description('Primary ticket and code information.')
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Unique Voucher Code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g. GIFT-2026-PLAY')
                                    ->default(fn() => 'V-' . Str::upper(Str::random(10))),
                                Forms\Components\TextInput::make('name')
                                    ->label('Internal Reference Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. First Visit Play Pass'),
                                Forms\Components\Select::make('voucher_type')
                                    ->label('Voucher Usage Mode')
                                    ->required()
                                    ->options([
                                        'gift_card' => 'Gift Balance Card',
                                        'trial_pass' => 'Trial / Free Pass',
                                        'service_coupon' => 'Specific Service Coupon',
                                        'discount' => 'General Percentage Discount',
                                    ])
                                    ->default('gift_card'),
                                Forms\Components\Select::make('status')
                                    ->required()
                                    ->options([
                                        'active' => 'Active',
                                        'used' => 'Redeemed',
                                        'expired' => 'Expired',
                                        'blocked' => 'Blocked (Suspicious Activity)',
                                    ])
                                    ->default('active'),
                            ])->columns(2),

                        Forms\Components\Section::make('Financial Parameters & Value')
                            ->schema([
                                Forms\Components\TextInput::make('face_value')
                                    ->label('Face Value (Kopecks)')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('RUB kop')
                                    ->helperText('Monetary value if type is balance card.'),
                                Forms\Components\TextInput::make('min_purchase_required')
                                    ->label('Min Purchase Constraint')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('RUB kop')
                                    ->helperText('Minimum basket value to apply this voucher.'),
                                Forms\Components\TextInput::make('max_discount_amount')
                                    ->label('Max Discount Cap')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('RUB kop'),
                            ])->columns(3),

                        Forms\Components\Section::make('Dates & Timing')
                            ->schema([
                                Forms\Components\DateTimePicker::make('valid_from')
                                    ->label('Valid From')
                                    ->default(now()),
                                Forms\Components\DateTimePicker::make('valid_until')
                                    ->label('Valid Until')
                                    ->nullable(),
                                Forms\Components\DateTimePicker::make('used_at')
                                    ->label('DateTime of Redemption')
                                    ->disabled()
                                    ->dehydrated(false),
                            ])->columns(3),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Ownership & Constraints')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Allocated User (Optional)')
                                    ->relationship('user', 'name')
                                    ->searchable(),
                                Forms\Components\Select::make('center_id')
                                    ->label('Valid only in specific Center')
                                    ->relationship('center', 'name')
                                    ->searchable(),
                            ]),
                        
                        Forms\Components\Section::make('Metadata & Trace')
                            ->schema([
                                Forms\Components\KeyValue::make('tags')
                                    ->label('Voucher Metadata Tags')
                                    ->keyLabel('Meta Key')
                                    ->valueLabel('Meta Value')
                                    ->default([
                                        'campaign' => 'spring-grand-tour',
                                        'source' => 'ai_constructor',
                                    ]),
                                Forms\Components\TextInput::make('correlation_id')
                                    ->label('Trace ID')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('uuid')
                                    ->label('UUID')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('voucher_type')
                    ->label('Type')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('face_value')
                    ->label('Value')
                    ->money('rub', 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'used' => 'gray',
                        'expired', 'blocked' => 'danger',
                        default => 'warning'
                    }),
                Tables\Columns\TextColumn::make('valid_until')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->valid_until && $record->valid_until->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'used' => 'Used',
                        'expired' => 'Expired',
                        'blocked' => 'Blocked',
                    ]),
                Tables\Filters\SelectFilter::make('voucher_type')
                    ->options([
                        'gift_card' => 'Gift Balance',
                        'trial_pass' => 'Trial Pass',
                        'service_coupon' => 'Coupon',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('Redeem')
                    ->label('Manual Redeem')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status !== 'active')
                    ->action(fn ($record) => $record->update(['status' => 'used', 'used_at' => now()])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('Block All')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'blocked'])),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKidsVouchers::route('/'),
            'create' => Pages\CreateKidsVoucher::route('/create'),
            'edit' => Pages\EditKidsVoucher::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
