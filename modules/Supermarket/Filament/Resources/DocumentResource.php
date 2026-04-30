<?php

declare(strict_types=1);

namespace Modules\Supermarket\Filament\Resources;

use Modules\Supermarket\Domain\Models\Document;
use Modules\Supermarket\Domain\Models\DocumentHistory;
use Modules\Supermarket\Domain\Models\SupplierTier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Supermarket';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Information')
                    ->schema([
                        Forms\Components\TextInput::make('document_number')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('document_type')
                            ->options([
                                'certificate' => 'Сертификат',
                                'license' => 'Лицензия',
                                'contract' => 'Договор',
                                'invoice' => 'Счёт',
                                'price_list' => 'Прайс-лист',
                                'catalog' => 'Каталог',
                                'quality_cert' => 'Сертификат качества',
                                'honest_mark' => 'Честный знак',
                                'other' => 'Другое',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3),

                        Forms\Components\Select::make('seller_id')
                            ->relationship('seller', 'name')
                            ->searchable()
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('supplier_tier_id')
                            ->relationship('supplierTier', 'name')
                            ->label('Supplier Tier')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Черновик',
                                'published' => 'Опубликован',
                                'expired' => 'Истёк',
                                'revoked' => 'Отозван',
                                'closed' => 'Закрыт',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('B2B Certificate Details')
                    ->schema([
                        Forms\Components\TextInput::make('batch_number')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('barcode')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('batch_weight')
                            ->numeric()
                            ->step(0.001)
                            ->suffix('кг'),

                        Forms\Components\TextInput::make('delivered_quantity')
                            ->numeric()
                            ->step(0.001)
                            ->suffix('кг')
                            ->disabled(),

                        Forms\Components\TextInput::make('remaining_quantity')
                            ->numeric()
                            ->step(0.001)
                            ->suffix('кг')
                            ->disabled(),

                        Forms\Components\Toggle::make('is_closed')
                            ->label('Certificate Closed'),

                        Forms\Components\Toggle::make('is_resale_blocked')
                            ->label('Resale Blocked'),

                        Forms\Components\Textarea::make('block_reason')
                            ->rows(2)
                            ->visible(fn (Forms\Get $get) => $get('is_resale_blocked')),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Certificate Validity')
                    ->schema([
                        Forms\Components\DatePicker::make('valid_from')
                            ->label('Valid From'),

                        Forms\Components\DatePicker::make('valid_to')
                            ->label('Valid To')
                            ->after('valid_from'),

                        Forms\Components\Toggle::make('is_valid')
                            ->label('Is Valid')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Supply Chain')
                    ->schema([
                        Forms\Components\Select::make('primary_document_id')
                            ->relationship('primaryDocument', 'document_number')
                            ->label('Primary Document (from Manufacturer)')
                            ->searchable()
                            ->preload()
                            ->helperText('Required for non-manufacturer tiers'),

                        Forms\Components\Toggle::make('requires_primary_document')
                            ->label('Requires Primary Document'),

                        Forms\Components\TextInput::make('chain_position')
                            ->numeric()
                            ->disabled()
                            ->label('Chain Position'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('document_type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'certificate' => 'Сертификат',
                        'license' => 'Лицензия',
                        'contract' => 'Договор',
                        'invoice' => 'Счёт',
                        'quality_cert' => 'Сертификат качества',
                        'honest_mark' => 'Честный знак',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('seller.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplierTier.name')
                    ->label('Tier')
                    ->sortable(),

                TextColumn::make('batch_number')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('batch_weight')
                    ->formatStateUsing(fn ($state): string => $state ? $state . ' кг' : '-')
                    ->toggleable(),

                TextColumn::make('delivered_quantity')
                    ->formatStateUsing(fn ($state): string => $state ? $state . ' кг' : '-')
                    ->toggleable(),

                TextColumn::make('remaining_quantity')
                    ->formatStateUsing(fn ($state): string => $state ? $state . ' кг' : '-')
                    ->toggleable(),

                TextColumn::make('valid_from')
                    ->date()
                    ->toggleable(),

                TextColumn::make('valid_to')
                    ->date()
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                        'danger' => 'expired',
                        'gray' => 'closed',
                    ]),

                IconColumn::make('is_valid')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->label('Valid'),

                IconColumn::make('is_closed')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->label('Closed'),

                IconColumn::make('is_resale_blocked')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->label('Blocked'),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->options([
                        'certificate' => 'Сертификат',
                        'license' => 'Лицензия',
                        'contract' => 'Договор',
                        'invoice' => 'Счёт',
                        'quality_cert' => 'Сертификат качества',
                        'honest_mark' => 'Честный знак',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Черновик',
                        'published' => 'Опубликован',
                        'expired' => 'Истёк',
                        'revoked' => 'Отозван',
                        'closed' => 'Закрыт',
                    ]),

                SelectFilter::make('supplier_tier_id')
                    ->relationship('supplierTier', 'name')
                    ->label('Supplier Tier'),

                TernaryFilter::make('is_valid')
                    ->label('Is Valid'),

                TernaryFilter::make('is_closed')
                    ->label('Is Closed'),

                TernaryFilter::make('is_resale_blocked')
                    ->label('Resale Blocked'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('validate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Document $record) {
                        $record->validateCertificate();
                    }),
                Action::make('viewHistory')
                    ->icon('heroicono-clock')
                    ->color('info')
                    ->url(fn (Document $record): string => route('filament.admin.resources.documents.history', $record->id)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('validateAll')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->validateCertificate();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            'history' => Tables\RelationManagers\RelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Supermarket\Filament\Resources\DocumentResource\Pages\ListDocuments::route('/'),
            'create' => \Modules\Supermarket\Filament\Resources\DocumentResource\Pages\CreateDocument::route('/create'),
            'edit' => \Modules\Supermarket\Filament\Resources\DocumentResource\Pages\EditDocument::route('/{record}/edit'),
            'history' => \Modules\Supermarket\Filament\Resources\DocumentResource\Pages\ViewDocumentHistory::route('/{record}/history'),
        ];
    }
}
