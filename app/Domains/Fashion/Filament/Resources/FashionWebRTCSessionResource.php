<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\FashionWebRTCSessionResource\Pages;

final class FashionWebRTCSessionResource extends Resource
{
    protected static ?string $model = \App\Models\FashionWebRTCSession::class;
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationGroup = 'Fashion AI';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Session Information')
                    ->schema([
                        Forms\Components\TextInput::make('session_token')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'initiated' => 'Initiated',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'expired' => 'Expired',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('scheduled_at'),
                        Forms\Components\DateTimePicker::make('started_at'),
                        Forms\Components\DateTimePicker::make('ended_at'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Session ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stylist.name')
                    ->label('Stylist')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'initiated',
                        'success' => 'active',
                        'gray' => 'completed',
                        'danger' => 'cancelled',
                        'danger' => 'expired',
                    ]),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'initiated' => 'Initiated',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),
                Tables\Filters\Filter::make('scheduled_today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFashionWebRTCSessions::route('/'),
            'create' => Pages\CreateFashionWebRTCSession::route('/create'),
            'view' => Pages\ViewFashionWebRTCSession::route('/{record}'),
            'edit' => Pages\EditFashionWebRTCSession::route('/{record}/edit'),
        ];
    }
}
