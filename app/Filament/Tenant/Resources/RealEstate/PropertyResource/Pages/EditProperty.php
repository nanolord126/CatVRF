<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;
use App\Filament\Tenant\Resources\RealEstate\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditProperty extends EditRecord { protected static string $resource = PropertyResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
