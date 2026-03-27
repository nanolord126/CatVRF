<?php

declare(strict_types=1);


namespace App\Domains\Sports\Fitness\Filament\Resources\MembershipResource\Pages;

use App\Domains\Sports\Fitness\Filament\Resources\MembershipResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateMembership
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateMembership extends CreateRecord
{
    protected static string $resource = MembershipResource::class;
}
