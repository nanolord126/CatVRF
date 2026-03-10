<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class AILogisticsCommunicationsDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static string $view = 'filament.tenant.pages.ai-logistics-communications-dashboard';
    protected static ?string $title = 'AI Supply & Smart Communications 2026';
    protected static ?string $navigationGroup = 'AI & Operations';

    /**
     * Define the data table for Inventory Proposals (AI Recommendations).
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(DB::table('predictive_stock_redistributions')->latest())
            ->columns([
                TextColumn::make('product_id')->label('Product')->sortable(),
                TextColumn::make('source_warehouse_id')->label('From')->sortable(),
                TextColumn::make('target_warehouse_id')->label('To')->sortable(),
                TextColumn::make('suggested_quantity')->label('Suggest Qty')->numeric(),
                TextColumn::make('confidence_level')
                    ->label('AI Confidence')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 1) . '%')
                    ->color(fn ($state) => $state > 0.8 ? 'success' : 'warning'),
                TextColumn::make('reason_tag')->badge(),
                TextColumn::make('status')->badge(),
            ])
            ->actions([
                Action::make('execute')
                    ->label('Execute Transfer')
                    ->color('success')
                    ->icon('heroicon-m-check-circle')
                    ->action(fn ($record) => DB::table('predictive_stock_redistributions')->where('id', $record->id)->update(['status' => 'completed'])),
                Action::make('cancel')
                    ->label('Reject')
                    ->color('gray')
                    ->icon('heroicon-m-x-circle')
                    ->action(fn ($record) => DB::table('predictive_stock_redistributions')->where('id', $record->id)->update(['status' => 'rejected'])),
            ]);
    }

    /**
     * Logic for Smart Notifications stream (Waitlist in queue).
     */
    protected function getViewData(): array
    {
        return [
            'notifications_queue' => DB::table('smart_notifications')
                ->where('scheduled_send_at', '>', now())
                ->latest('scheduled_send_at')
                ->get(),
            'sent_stats' => DB::table('smart_notifications')->count()
        ];
    }
}
