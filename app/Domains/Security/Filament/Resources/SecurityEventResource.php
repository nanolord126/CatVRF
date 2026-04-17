<?php declare(strict_types=1);

namespace App\Domains\Security\Filament\Resources;

use App\Domains\Security\Models\SecurityEvent;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class SecurityEventResource extends Resource
{
    protected static ?string $model = SecurityEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Security';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'critical' => 'Critical',
                        'warning' => 'Warning',
                        'info' => 'Info',
                    ]),
                Tables\Filters\SelectFilter::make('event_type')
                    ->options([
                        'api_key_generated' => 'API Key Generated',
                        'api_key_revoked' => 'API Key Revoked',
                        'rate_limit_exceeded' => 'Rate Limit Exceeded',
                        'api_key_validation_failed' => 'API Key Validation Failed',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([]);
    }
}
