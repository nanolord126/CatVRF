<?php

namespace App\Filament\Tenant\Resources\B2B;

use App\Filament\Tenant\Resources\B2B\SupplierResource\Pages;
use App\Models\B2B\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'B2B Supply Chain';
    protected static ?string $label = 'Поставщик';
    protected static ?string $pluralLabel = 'Поставщики';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Info')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->label('Название компании'),
                        Forms\Components\TextInput::make('tax_id')->label('ИНН / Tax ID'),
                        Forms\Components\TextInput::make('credit_limit')->numeric()->prefix('$')->label('Кредитный лимит (B2B Credit)'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'ACTIVE' => 'Работаем',
                                'SUSPENDED' => 'Приостановлен',
                                'BLOCKED' => 'Заблокирован',
                            ])->default('ACTIVE')->required(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Contacts & Geo')
                    ->schema([
                        Forms\Components\TextInput::make('email')->email(),
                        Forms\Components\TextInput::make('phone')->tel(),
                        Forms\Components\TextInput::make('address')->columnSpanFull(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->label('Компания'),
                Tables\Columns\TextColumn::make('tax_id')->label('ИНН'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'ACTIVE',
                        'warning' => 'SUSPENDED',
                        'danger' => 'BLOCKED',
                    ]),
                Tables\Columns\TextColumn::make('credit_limit')->money('USD')->label('Лимит'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'ACTIVE' => 'Active',
                    'BLOCKED' => 'Blocked',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
