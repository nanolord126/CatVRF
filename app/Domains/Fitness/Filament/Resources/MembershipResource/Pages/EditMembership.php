<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\MembershipResource\Pages;

use App\Domains\Fitness\Filament\Resources\MembershipResource;
use Filament\Resources\Pages\EditRecord;

final class EditMembership extends EditRecord
{
    protected static string $resource = MembershipResource::class;
}
