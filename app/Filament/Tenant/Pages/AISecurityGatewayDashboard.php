<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class AISecurityGatewayDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static string $view = 'filament.tenant.pages.ai-security-gateway-dashboard';
    protected static ?string $title = 'AI Reputation & API Gateway 2026';
    protected static ?string $navigationGroup = 'AI & Security';

    /**
     * Define the summary of AI Fraud Detections (Table 1).
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(DB::table('ai_fraud_detections')->latest())
            ->columns([
                TextColumn::make('flag_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fake_review' => 'warning',
                        'gps_spoofing' => 'danger',
                        'payment_wash' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('user_id')->label('User ID')->sortable(),
                TextColumn::make('entity_type')->label('Target'),
                TextColumn::make('probability')
                    ->label('AI Confidence')
                    ->numeric(2)
                    ->suffix('%')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2))
                    ->color(fn ($state) => $state > 0.8 ? 'danger' : ($state > 0.5 ? 'warning' : 'success')),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Action::make('confirm')
                    ->label('Confirm Fraud')
                    ->color('danger')
                    ->icon('heroicon-m-check-circle')
                    ->action(fn ($record) => DB::table('ai_fraud_detections')->where('id', $record->id)->update(['status' => 'confirmed'])),
                Action::make('dismiss')
                    ->label('Dismiss')
                    ->color('gray')
                    ->icon('heroicon-m-x-circle')
                    ->action(fn ($record) => DB::table('ai_fraud_detections')->where('id', $record->id)->update(['status' => 'dismissed'])),
            ]);
    }

    /**
     * Logic for partners view (passed as data to the view).
     */
    protected function getViewData(): array
    {
        return [
            'partners' => DB::table('partner_api_gateways')->get(),
            'apiUsage' => DB::table('api_gateway_logs')
                ->select('endpoint', DB::raw('count(*) as count'), DB::raw('avg(latency_ms) as avg_latency'))
                ->groupBy('endpoint')
                ->get()
        ];
    }
}
