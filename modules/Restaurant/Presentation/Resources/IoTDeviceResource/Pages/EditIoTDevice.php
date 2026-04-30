<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources\IoTDeviceResource\Pages;

use Modules\Restaurant\Presentation\Resources\IoTDeviceResource;
use Filament\Resources\Pages\EditRecord;

final class EditIoTDevice extends EditRecord
{
    protected static string $resource = IoTDeviceResource::class;
}
