<?php declare(strict_types=1);

namespace App\Domains\Audit\Filament\Resources;

use App\Domains\Audit\Models\AuditLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('correlation_id')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('action')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('action'),
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['action'])) {
                            $query->where('action', 'like', '%' . $data['action'] . '%');
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([]);
    }
}
