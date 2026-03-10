<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\PayrollRunResource\Pages;
use App\Models\PayrollRun;
use App\Models\User;
use App\Models\SalarySlip;
use App\Models\EmployeeDeduction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Bavix\Wallet\Models\Wallet;

class PayrollRunResource extends Resource
{
    protected static ?string $model = PayrollRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Payroll Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::required('period_start'),
                Forms\Components\DatePicker::required('period_end'),
                Forms\Components\TextInput::make('status')->disabled()->default('draft'),
                Forms\Components\TextInput::make('total_amount')->disabled()->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_start')->date(),
                Tables\Columns\TextColumn::make('period_end')->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'processed' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_amount')->money('USD'),
                Tables\Columns\TextColumn::make('processed_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\Action::make('generateSlips')
                    ->label('Generate Slips')
                    ->icon('heroicon-o-cpu-chip')
                    ->action(fn (PayrollRun $record) => static::calculatePayroll($record))
                    ->visible(fn (PayrollRun $record) => $record->status === 'draft'),
                Tables\Actions\Action::make('processPayment')
                    ->label('Process Payment')
                    ->icon('heroicon-o-banknotes')
                    ->requiresConfirmation()
                    ->action(fn (PayrollRun $record) => static::processPayment($record))
                    ->visible(fn (PayrollRun $record) => $record->status === 'draft' && $record->slips()->count() > 0),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    protected static function calculatePayroll(PayrollRun $record) 
    {
        DB::transaction(function () use ($record) {
            $record->slips()->delete();
            $correlationId = Str::uuid()->toString();
            $totalAmount = 0;

            $users = User::with(['payrollConfig', 'deductions' => function($q) use ($record) {
                $q->whereBetween('date', [$record->period_start, $record->period_end])->where('status', 'pending');
            }])->get();

            foreach ($users as $user) {
                if (!$user->payrollConfig) continue;

                $baseSalary = $user->payrollConfig->base_salary;
                $commissions = 0; // Mock logic, ideally fetch from master_appointments or hotel_bookings
                $bonuses = 0; 
                $deductions = $user->deductions->sum('amount');
                
                $netSalary = $baseSalary + $commissions + $bonuses - $deductions;

                SalarySlip::create([
                    'payroll_run_id' => $record->id,
                    'user_id' => $user->id,
                    'base_salary' => $baseSalary,
                    'commissions' => $commissions,
                    'bonuses' => $bonuses,
                    'deductions' => $deductions,
                    'net_salary' => $netSalary,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                ]);

                $totalAmount += $netSalary;
            }

            $record->update([
                'total_amount' => $totalAmount,
                'correlation_id' => $correlationId,
            ]);
        });

        Notification::make()->title('Slips Generated Successfully')->success()->send();
    }

    protected static function processPayment(PayrollRun $record)
    {
        $tenant = tenant();
        if (!$tenant || !$tenant->hasWallet('business')) {
            Notification::make()->title('Business Wallet not found for Tenant')->danger()->send();
            return;
        }

        $wallet = $tenant->getWallet('business');
        $amountToPay = $record->total_amount;

        if ($wallet->balance < $amountToPay) {
            Notification::make()->title('Insufficient funds in Business Wallet')->danger()->send();
            return;
        }

        DB::transaction(function () use ($record, $wallet, $amountToPay) {
            $correlationId = Str::uuid()->toString();

            // Deduct from business wallet using correlation_id
            $wallet->withdraw($amountToPay, [
                'description' => "Payroll for period {$record->period_start} to {$record->period_end}",
                'correlation_id' => $correlationId,
                'payroll_run_id' => $record->id,
            ]);

            $record->slips()->update(['status' => 'paid', 'correlation_id' => $correlationId]);
            
            // Mark deductions as applied
            EmployeeDeduction::whereBetween('date', [$record->period_start, $record->period_end])
                ->whereIn('user_id', $record->slips()->pluck('user_id'))
                ->update(['status' => 'applied', 'correlation_id' => $correlationId]);

            $record->update([
                'status' => 'processed',
                'processed_at' => now(),
                'correlation_id' => $correlationId,
            ]);
        });

        Notification::make()->title('Payments Processed Successfully')->success()->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollRuns::route('/'),
            'create' => Pages\CreatePayrollRun::route('/create'),
            'view' => Pages\ViewPayrollRun::route('/{record}'),
            'edit' => Pages\EditPayrollRun::route('/{record}/edit'),
        ];
    }
}
