<?php
// Очистить BOM и переформатировать файл
$file = 'C:\\opt\\kotvrf\\CatVRF\\app\\Filament\\Admin\\Resources\\AdminResource\\Pages\\Settings\\TransitionConfirmationPage.php';

$content = <<<'PHP'
<?php declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdminResource\Pages\Settings;

use App\Filament\Admin\Resources\AdminResource;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class TransitionConfirmationPage extends Page
{
    protected static string $resource = AdminResource::class;

    protected static ?string $title = 'Подтверждение системного перехода';

    protected static ?string $breadcrumb = 'Подтверждение перехода';

    protected static string $view = 'filament.admin.resources.admin-resource.pages.settings.transition-confirmation';

    /**
     * @var Guard
     */
    protected Guard $guard;

    /**
     * @param Guard $guard
     * @return void
     */
    public function boot(Guard $guard): void
    {
        $this->guard = $guard;
    }

    /**
     * @return void
     */
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        Log::channel('audit')->info('Admin accessed transition confirmation page', [
            'admin_id' => $this->guard->id(),
            'correlation_id' => Str::uuid()->toString(),
        ]);
    }
}
PHP;

// Использовать UTF-8 без BOM
file_put_contents($file, $content, FILE_TEXT);
echo "File written without BOM\n";
