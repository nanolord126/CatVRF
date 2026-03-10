<?php

namespace App\Filament\Tenant\Resources\Finance;

use App\Models\SettlementDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;

class SettlementDocumentResource extends Resource
{
    protected static ?string $model = SettlementDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Финансы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('number')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->options([
                        'invoice' => 'Счет',
                        'act' => 'Акт',
                        'upd' => 'УПД',
                    ])
                    ->required(),
                DatePicker::make('document_date')
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->required(),
                Select::make('status')
                    ->options([
                        'draft' => 'Черновик',
                        'sent' => 'Отправлен',
                        'signed' => 'Подписан',
                        'cancelled' => 'Аннулирован',
                    ])
                    ->default('draft')
                    ->required(),
                FileUpload::make('file_path')
                    ->label('Оригинал документа')
                    ->directory('settlements'),
                FileUpload::make('signed_file_path')
                    ->label('Подписанная версия')
                    ->directory('settlements/signed'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('document_date')->date()->sortable(),
                TextColumn::make('amount')->money('RUB'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'invoice' => 'Счет',
                        'act' => 'Акт',
                        'upd' => 'УПД',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Черновик',
                        'sent' => 'Отправлен',
                        'signed' => 'Подписан',
                        'cancelled' => 'Аннулирован',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DownloadAction::make('download_signed')
                    ->label('Скачать скан')
                    ->url(fn (SettlementDocument $record) => $record->signed_file_path ? asset('storage/' . $record->signed_file_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (SettlementDocument $record) => $record->signed_file_path !== null),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Finance\SettlementDocumentResource\Pages\ListSettlementDocuments::route('/'),
        ];
    }
}
