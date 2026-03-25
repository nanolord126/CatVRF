declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Channels\Pages;

use App\Filament\Tenant\Resources\Channels\ChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditChannel
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditChannel extends EditRecord
{
    protected static string $resource = ChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
