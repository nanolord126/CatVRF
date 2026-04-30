<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Corporate;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Corporate\CorporateClientModel;

final class CorporateClientResource extends Resource
{
    protected static ?string $model = CorporateClientModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Fitness - Corporate';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Company Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Company Name'),
                        
                        Forms\Components\TextInput::make('inn')
                            ->required()
                            ->maxLength(12)
                            ->label('INN')
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\Textarea::make('legal_address')
                            ->required()
                            ->maxLength(65535)
                            ->label('Legal Address')
                            ->rows(2),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('contact_person')
                            ->required()
                            ->maxLength(255)
                            ->label('Contact Person'),
                        
                        Forms\Components\TextInput::make('hr_email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->label('HR Email'),
                        
                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->maxLength(20)
                            ->label('Phone Number'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Contract Information')
                    ->schema([
                        Forms\Components\TextInput::make('contract_number')
                            ->maxLength(255)
                            ->label('Contract Number'),
                        
                        Forms\Components\DatePicker::make('contract_date')
                            ->label('Contract Date'),
                        
                        Forms\Components\DatePicker::make('contract_end_date')
                            ->label('Contract End Date')
                            ->after('contract_date'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->label('Notes')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Company Name'),
                
                Tables\Columns\TextColumn::make('inn')
                    ->searchable()
                    ->label('INN'),
                
                Tables\Columns\TextColumn::make('contact_person')
                    ->searchable()
                    ->label('Contact Person'),
                
                Tables\Columns\TextColumn::make('hr_email')
                    ->searchable()
                    ->label('HR Email'),
                
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone'),
                
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Contract #'),
                
                Tables\Columns\IconColumn::make('has_active_contract')
                    ->boolean()
                    ->label('Active Contract')
                    ->getStateUsing(fn ($record) => $record->contract_end_date === null || $record->contract_end_date->isFuture()),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Created At')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('active_contracts')
                    ->query(fn ($query) => $query->where(function ($q) {
                        $q->whereNull('contract_end_date')
                            ->orWhere('contract_end_date', '>', now());
                    }))
                    ->label('Active Contracts'),
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
            'index' => \Modules\Fitness\Filament\Resources\Corporate\Pages\ListCorporateClients::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Corporate\Pages\CreateCorporateClient::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Corporate\Pages\EditCorporateClient::route('/{record}/edit'),
        ];
    }
}
