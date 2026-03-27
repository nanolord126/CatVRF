<?php

declare(strict_types=1);


namespace App\Domains\Sports\Fitness\Filament\Resources\FitnessClassResource\Pages;

use App\Domains\Sports\Fitness\Filament\Resources\FitnessClassResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditFitnessClass
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditFitnessClass extends EditRecord
{
    protected static string $resource = FitnessClassResource::class;
}
