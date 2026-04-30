<?php

declare(strict_types=1);

namespace Filament\Resources;

use App\Octane\Services\SwooleTableService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

final class OctaneHealthResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationLabel = 'Octane Health';

    protected static ?string $model = null;

    protected static ?string $navigationGroup = 'System';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view octane health') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Swoole Server Status')
                    ->schema([
                        Forms\Components\Placeholder::make('server_status')
                            ->content(fn () => new HtmlString(
                                function_exists('swoole_server')
                                    ? '<span class="text-green-600 font-bold">● Running</span>'
                                    : '<span class="text-red-600 font-bold">● Not Installed</span>'
                            )),

                        Forms\Components\Placeholder::make('worker_count')
                            ->label('HTTP Workers')
                            ->content(fn () => config('octane.swoole.worker_count', 'N/A')),

                        Forms\Components\Placeholder::make('task_worker_count')
                            ->label('Task Workers')
                            ->content(fn () => config('octane.swoole.task_worker_count', 'N/A')),

                        Forms\Components\Placeholder::make('coroutine_enabled')
                            ->label('Coroutines Enabled')
                            ->content(fn () => config('octane.swoole.enable_coroutine', false) ? 'Yes' : 'No'),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // Return empty query as this is a dashboard resource
                return Model::query();
            })
            ->columns([
                Tables\Columns\TextColumn::make('table_name')
                    ->label('Table Name'),
                Tables\Columns\TextColumn::make('size')
                    ->label('Max Size'),
                Tables\Columns\TextColumn::make('count')
                    ->label('Current Count'),
                Tables\Columns\TextColumn::make('memory_size')
                    ->label('Memory (bytes)'),
                Tables\Columns\TextColumn::make('usage_percent')
                    ->label('Usage %')
                    ->formatStateUsing(fn ($state) => number_format($state, 2).'%'),
            ])
            ->defaultPaginationPageOption(50)
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecords::route('/'),
        ];
    }

    public static function getTableData(): array
    {
        try {
            $tableService = app(SwooleTableService::class);
            $stats = $tableService->getStats();

            $data = [];
            foreach ($stats as $name => $stat) {
                $data[] = [
                    'table_name' => $name,
                    'size' => $stat['size'],
                    'count' => $stat['count'],
                    'memory_size' => number_format($stat['memory_size']),
                    'usage_percent' => $stat['size'] > 0
                        ? ($stat['count'] / $stat['size']) * 100
                        : 0,
                ];
            }

            return $data;
        } catch (\Throwable $e) {
            return [[
                'table_name' => 'Error',
                'size' => 'N/A',
                'count' => 'N/A',
                'memory_size' => $e->getMessage(),
                'usage_percent' => 0,
            ]];
        }
    }
}
