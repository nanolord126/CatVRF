<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Program\Pages;
use App\Filament\Tenant\Resources\ProgramResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordProgram extends EditRecord {
    protected static string $resource = ProgramResource::class;
}
