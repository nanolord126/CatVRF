<?php

declare(strict_types=1);


namespace App\Domains\Sports\Fitness\Filament\Resources\TrainerResource\Pages;

use App\Domains\Sports\Fitness\Filament\Resources\TrainerResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditTrainer
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditTrainer extends EditRecord
{
    protected static string $resource = TrainerResource::class;
}
