<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Modules\BeautyMasters\Infrastructure\Models\BeautyCertificationModel;
use Modules\BeautyMasters\Infrastructure\Models\MakeupCertificationModel;
use Modules\BeautyMasters\Infrastructure\Models\BrowCertificationModel;
use Modules\BeautyMasters\Infrastructure\Models\LashCertificationModel;
use Illuminate\Support\Facades\DB;

final class ExpiringCertificationsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $beauty = BeautyCertificationModel::where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(60))
            ->select('master_id', 'name as certification_name', 'expiry_date', DB::raw("'beauty' as vertical"));

        $makeup = MakeupCertificationModel::where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(60))
            ->select('master_id', 'name as certification_name', 'expiry_date', DB::raw("'makeup' as vertical"));

        $brow = BrowCertificationModel::where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(60))
            ->select('master_id', 'name as certification_name', 'expiry_date', DB::raw("'brow' as vertical"));

        $lash = LashCertificationModel::where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(60))
            ->select('master_id', 'name as certification_name', 'expiry_date', DB::raw("'lash' as vertical"));

        $expiring = $beauty->union($makeup)->union($brow)->union($lash)
            ->orderBy('expiry_date')
            ->get();

        return $table
            ->query($expiring)
            ->columns([
                Tables\Columns\TextColumn::make('vertical')
                    ->label('Vertical')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'beauty' => 'primary',
                        'makeup' => 'pink',
                        'brow' => 'warning',
                        'lash' => 'purple',
                    }),

                Tables\Columns\TextColumn::make('certification_name')
                    ->label('Certification')
                    ->searchable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date()
                    ->sortable()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('days_until_expiry')
                    ->label('Days Until Expiry')
                    ->getStateUsing(fn ($record) => $record->expiry_date->diffInDays(now()))
                    ->color(fn (int $state): string => $state <= 30 ? 'danger' : 'warning'),
            ])
            ->defaultPaginationPageOption(10);
    }
}
