<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\EmployeeDeductionResource\Pages;
use App\Models\EmployeeDeduction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EmployeeDeductionResource extends Resource
{
    protected static ?string $model = EmployeeDeduction::class;

    protected static ?string $navigationIcon = 'heroicon-o-minus-circle';

    protected static ?string $navigationGroup = 'Payroll Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\TextInput::make('reason')
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now()),
                Forms\Components\Hidden::make('correlation_id')
                    ->default(fn () => Str::uuid()->toString()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable(),
                Tables\Columns\TextColumn::make('amount')->money('USD'),
                Tables\Columns\TextColumn::make('reason'),
                Tables\Columns\TextColumn::make('date')->date(),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeDeductions::route('/'),
            'create' => Pages\CreateEmployeeDeduction::route('/create'),
            'edit' => Pages\EditEmployeeDeduction::route('/{record}/edit'),
        ];
    }
}
