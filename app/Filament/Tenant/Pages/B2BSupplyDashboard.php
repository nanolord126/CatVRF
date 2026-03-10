<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Models\B2B\Supplier;
use App\Models\B2B\PurchaseOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\User;
use App\Filament\Widgets\B2BDemandHeatmapWidget;
use App\Filament\Widgets\B2BRecommendedSuppliersWidget;

class B2BSupplyDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'B2B Supply Chain';
    protected static ?string $title = 'B2B Marketplace & AI Procurement';
    protected static string $view = 'filament.tenant.pages.b2b-supply-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            B2BRecommendedSuppliersWidget::class,
            B2BDemandHeatmapWidget::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PurchaseOrder::query()->where('payment_status', 'UNPAID'))
            ->heading('Ожидающие оплаты закупки')
            ->columns([
                Tables\Columns\TextColumn::make('order_number'),
                Tables\Columns\TextColumn::make('supplier.name'),
                Tables\Columns\TextColumn::make('total_amount')->money('USD'),
                Tables\Columns\BadgeColumn::make('status'),
            ])
            ->actions([
                Tables\Actions\Action::make('pay')
                    ->label('Оплатить Кошельком')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->action(fn (PurchaseOrder $record) => (new \App\Services\B2B\B2BWalletPaymentService())->payPurchaseOrder($record)),
            ]);
    }

    public function getStats(): array
    {
        return [
            'total_suppliers' => Supplier::count(),
            'outstanding_debt' => PurchaseOrder::where('payment_status', 'UNPAID')->sum('total_amount'),
            'wallet_balance' => auth()->user()->balance ?? 0.00,
        ];
    }
}
