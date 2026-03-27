<?php

declare(strict_types=1);


namespace App\Domains\Sports\Fitness\Filament\Resources\GymResource\Pages;

use App\Domains\Sports\Fitness\Filament\Resources\GymResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateGym
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateGym extends CreateRecord
{
    protected static string $resource = GymResource::class;
}
