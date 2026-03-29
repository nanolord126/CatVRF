<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Program\Pages;
use App\Filament\Tenant\Resources\ProgramResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordProgram extends CreateRecord {
    protected static string $resource = ProgramResource::class;
}
