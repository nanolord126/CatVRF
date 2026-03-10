<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Models\Common\HealthRecommendation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Facades\Auth;

class HealthDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.tenant.pages.health-dashboard';
    protected static ?string $title = 'Аналитика Здоровья (AI Dashboard)';
    protected static ?string $navigationGroup = 'Personal';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HealthRecommendation::where('user_id', Auth::id())
                    ->where('is_completed', true)
                    ->latest()
                    ->limit(5)
            )
            ->heading('Последние выполненные задачи')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Задача')
                    ->description(fn ($record) => $record->description),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Дата выполнения')
                    ->dateTime()
                    ->since(),
                Tables\Columns\BadgeColumn::make('target_type')
                    ->label('Кому?')
                    ->colors([
                        'primary' => 'HUMAN',
                        'purple' => 'ANIMAL',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'ANIMAL' ? '🐾' : '👤'),
            ]);
    }
}
