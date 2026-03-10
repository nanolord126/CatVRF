<?php

namespace App\Filament\Tenant\Resources\Finance;

use App\Models\AIReport;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class AIReportResource extends Resource
{
    protected static ?string $model = AIReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Финансы';
    protected static ?string $label = 'Отчёты';
    protected static ?string $pluralLabel = 'Отчёты';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('report_date')->date()->label('Дата')->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'daily' ? 'Ежедневный' : 'Еженедельный')
                    ->label('Тип'),
                TextColumn::make('data.revenue')
                    ->money('RUB')
                    ->label('Выручка'),
                TextColumn::make('data.orders_count')
                    ->label('Заказы'),
                IconColumn::make('sent_at')
                    ->boolean()
                    ->label('Отправлен')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->actions([
                Action::make('download_pdf')
                    ->label('Скачать PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (AIReport $record) {
                        return self::generateAndDownloadPdf($record);
                    }),
                Action::make('send_email')
                    ->label('Отправить на email')
                    ->icon('heroicon-o-envelope')
                    ->requiresConfirmation()
                    ->action(function (AIReport $record) {
                        // Логика отправки
                        $record->update(['sent_at' => now()]);
                        Notification::make()
                            ->title('Отчёт отправлен')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('generate_today')
                    ->label('Сгенерировать сейчас')
                    ->action(function () {
                        // Мок генерации
                        AIReport::create([
                            'type' => 'daily',
                            'report_date' => now()->subDay(),
                            'data' => [
                                'revenue' => rand(50000, 150000),
                                'orders_count' => rand(10, 50),
                                'ai_recommendations' => 'Увеличьте запасы цветов к празднику. Оптимизируйте графики курьеров с 18:00.',
                            ]
                        ]);
                    })
            ]);
    }

    protected static function generateAndDownloadPdf(AIReport $record)
    {
        $rec = $record->data['ai_recommendations'] ?? 'Нет данных';
        $pdf = Pdf::loadHTML("
            <h1>Отчет за {$record->report_date->format('d.m.Y')}</h1>
            <p>Тип: {$record->type}</p>
            <p>Выручка: {$record->data['revenue']} RUB</p>
            <p>Заказы: {$record->data['orders_count']}</p>
            <hr>
            <h3>AI Рекомендации:</h3>
            <p>{$rec}</p>
        ");
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "report-{$record->id}.pdf");
    }

    public static function getPages(): array
    {
        return [
            'index' => AIReportResource\Pages\ListAIReports::route('/'),
        ];
    }
}
