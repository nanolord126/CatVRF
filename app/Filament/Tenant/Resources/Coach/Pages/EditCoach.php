<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Coach\Pages;
use App\Filament\Tenant\Resources\CoachResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordCoach extends EditRecord {
    protected static string $resource = CoachResource::class;
}
