<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CdnResource\Pages;
use App\Services\Media\CdnMediaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

final class CdnResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Media & CDN';
    protected static ?int $navigationSort = 1;

    public static function getModel(): string
    {
        return \stdClass::class; // No model, uses service
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('CDN Configuration')
                    ->description('Configure your CDN settings for image and video optimization')
                    ->schema([
                        Forms\Components\Select::make('provider')
                            ->label('CDN Provider')
                            ->options([
                                'cloudflare_images' => 'Cloudflare Images',
                                'bunny' => 'Bunny CDN',
                            ])
                            ->required()
                            ->default(config('cdn.provider')),

                        Forms\Components\Toggle::make('enabled')
                            ->label('Enable CDN')
                            ->default(config('cdn.enabled', false))
                            ->helperText('When disabled, local storage will be used'),

                        Forms\Components\Section::make('Cloudflare Images Settings')
                            ->visible(fn (Forms\Get $get) => $get('provider') === 'cloudflare_images')
                            ->schema([
                                Forms\Components\TextInput::make('cloudflare_account_id')
                                    ->label('Account ID')
                                    ->default(config('cdn.cloudflare.account_id'))
                                    ->required(),
                                Forms\Components\TextInput::make('cloudflare_api_key')
                                    ->label('API Key')
                                    ->password()
                                    ->default(config('cdn.cloudflare.api_key'))
                                    ->required(),
                            ]),

                        Forms\Components\Section::make('Bunny CDN Settings')
                            ->visible(fn (Forms\Get $get) => $get('provider') === 'bunny')
                            ->schema([
                                Forms\Components\TextInput::make('bunny_api_key')
                                    ->label('API Key')
                                    ->password()
                                    ->default(config('cdn.bunny.api_key'))
                                    ->required(),
                                Forms\Components\TextInput::make('bunny_pull_zone')
                                    ->label('Pull Zone')
                                    ->default(config('cdn.bunny.pull_zone'))
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $cdnService = app(CdnMediaService::class);
        $stats = $cdnService->getCdnStats();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('metric')
                    ->label('Metric'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Value'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description'),
            ])
            ->defaultPaginationPageOption(50)
            ->paginated(false)
            ->data([
                [
                    'metric' => 'Provider',
                    'value' => $stats['provider'] ?? 'N/A',
                    'description' => 'Current CDN provider',
                ],
                [
                    'metric' => 'Status',
                    'value' => $stats['enabled'] ? 'Enabled' : 'Disabled',
                    'description' => 'CDN is currently ' . ($stats['enabled'] ? 'enabled' : 'disabled'),
                ],
                [
                    'metric' => 'Video Provider',
                    'value' => $stats['video_provider'] ?? 'N/A',
                    'description' => 'Video streaming CDN provider',
                ],
                [
                    'metric' => 'Video Status',
                    'value' => $stats['video_enabled'] ? 'Enabled' : 'Disabled',
                    'description' => 'Video CDN is currently ' . ($stats['video_enabled'] ? 'enabled' : 'disabled'),
                ],
            ])
            ->headerActions([
                Tables\Actions\Action::make('refresh')
                    ->label('Refresh Stats')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        Cache::forget('cdn_stats');
                    }),
                Tables\Actions\Action::make('clear_cache')
                    ->label('Clear CDN Cache')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function () use ($cdnService) {
                        $cdnService->invalidateAllCache();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCdns::route('/'),
            'create' => Pages\CreateCdn::route('/create'),
            'edit' => Pages\EditCdn::route('/{record}/edit'),
        ];
    }
}
