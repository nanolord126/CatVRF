<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\SalarySlipResource\Pages;
use App\Models\SalarySlip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class SalarySlipResource extends Resource
{
    protected static ?string $model = SalarySlip::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Payroll Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('payroll_run_id')->relationship('payrollRun', 'id')->disabled(),
                Forms\Components\Select::make('user_id')->relationship('user', 'name')->disabled(),
                Forms\Components\TextInput::make('base_salary')->numeric()->disabled(),
                Forms\Components\TextInput::make('commissions')->numeric()->disabled(),
                Forms\Components\TextInput::make('bonuses')->numeric()->disabled(),
                Forms\Components\TextInput::make('deductions')->numeric()->disabled(),
                Forms\Components\TextInput::make('net_salary')->numeric()->disabled(),
                Forms\Components\TextInput::make('status')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable(),
                Tables\Columns\TextColumn::make('payrollRun.period_start')->label('Period Start')->date(),
                Tables\Columns\TextColumn::make('net_salary')->money('USD'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->actions([
                Tables\Actions\Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => Notification::make()->title('PDF Generation is being mocked...')->info()->send()),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalarySlips::route('/'),
            'view' => Pages\ViewSalarySlip::route('/{record}'),
        ];
    }
}
