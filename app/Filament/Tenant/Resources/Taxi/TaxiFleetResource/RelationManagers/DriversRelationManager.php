<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Taxi\TaxiFleetResource\RelationManagers;

use App\Domains\Auto\Taxi\Application\B2B\UseCases\AddDriverToFleetUseCase;
use App\Domains\Auto\Taxi\Domain\ValueObjects\DriverId;
use App\Domains\Auto\Taxi\Domain\ValueObjects\TaxiFleetId;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

final class DriversRelationManager extends RelationManager
{
    protected static string $relationship = 'drivers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('license_number'),
                Tables\Columns\IconColumn::make('is_available')->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->action(function (array $data, RelationManager $livewire) {
                        $useCase = app(AddDriverToFleetUseCase::class);
                        $fleetId = new TaxiFleetId($livewire->ownerRecord->id);
                        $correlationId = Str::uuid()->toString();

                        foreach ($data['record_ids'] as $driverId) {
                            $useCase($fleetId, new DriverId($driverId), $correlationId);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
