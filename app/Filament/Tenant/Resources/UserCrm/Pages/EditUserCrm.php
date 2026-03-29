<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\UserCrm\Pages;
use App\Filament\Tenant\Resources\UserCrmResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordUserCrm extends EditRecord {
    protected static string $resource = UserCrmResource::class;
}
