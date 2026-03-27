<?php

declare(strict_types=1);


namespace App\Domains\HomeServices\Filament\Resources\ServiceReviewResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceReviewResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateServiceReview
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateServiceReview extends CreateRecord
{
    protected static string $resource = ServiceReviewResource::class;
}
