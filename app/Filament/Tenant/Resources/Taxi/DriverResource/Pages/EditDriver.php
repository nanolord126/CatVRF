<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Taxi\DriverResource\Pages;
use App\Filament\Tenant\Resources\Taxi\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditDriver extends EditRecord { protected static string $resource = DriverResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
