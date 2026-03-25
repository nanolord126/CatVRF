<?php declare(strict_types=1);

namespace App\Providers\Filament;

use App\Domains\Beauty\Filament\Resources\AppointmentResource;
use App\Domains\Beauty\Filament\Resources\BeautySalonResource;
use App\Domains\Beauty\Filament\Resources\BeautyServiceResource;
use App\Domains\Beauty\Filament\Resources\ReviewResource;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

final class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $tenantResources = $this->discoverTenantResources();

        $beautyResources = [];

        $requiredResources = [
            BeautySalonResource::class,
            BeautyServiceResource::class,
            AppointmentResource::class,
            ReviewResource::class,
        ];

        foreach ($requiredResources as $requiredResource) {
            if ($this->isFilamentV3ResourceCompatible($requiredResource)) {
                $beautyResources[] = $requiredResource;
            }
        }

        $optionalResources = [
            'App\\Domains\\Beauty\\Filament\\Resources\\MasterResource',
            'App\\Domains\\Beauty\\Filament\\Resources\\PortfolioItemResource',
            'App\\Domains\\Beauty\\Filament\\Resources\\ReviewResource',
            'App\\Domains\\Beauty\\Filament\\Resources\\BeautyProductResource',
            'App\\Domains\\Beauty\\Filament\\Resources\\BeautyProductItemResource',
            'App\\Domains\\Beauty\\Filament\\BeautyProductResource',
        ];

        foreach ($optionalResources as $optionalResource) {
            if (
                $this->isFilamentV3ResourceCompatible($optionalResource)
                && !in_array($optionalResource, $beautyResources, true)
            ) {
                $beautyResources[] = $optionalResource;
            }
        }

        $tenantResources = array_values(array_unique(array_merge($tenantResources, $beautyResources)));

        return $panel
            ->id('tenant')
            ->path('tenant')
            ->login()
            ->maxContentWidth('full')
            ->resources($tenantResources)
            ->navigationGroups([
                NavigationGroup::make('Красота и Бьюти')
                    ->icon('heroicon-o-scissors')
                    ->collapsed(false),
            ])
            ->navigationItems([
                NavigationItem::make('Салоны красоты')
                    ->group('Красота и Бьюти')
                    ->icon('heroicon-o-scissors')
                    ->sort(10)
                    ->url(fn (): string => url('/admin/marketplace/beauty/salons')),
                NavigationItem::make('Услуги')
                    ->group('Красота и Бьюти')
                    ->icon('heroicon-o-sparkles')
                    ->sort(20)
                    ->url(fn (): string => url('/admin/marketplace/beauty/services')),
                NavigationItem::make('Записи')
                    ->group('Красота и Бьюти')
                    ->icon('heroicon-o-calendar')
                    ->sort(30)
                    ->url(fn (): string => url('/admin/marketplace/beauty/bookings')),
                NavigationItem::make('Мастера (Стилисты)')
                    ->group('Красота и Бьюти')
                    ->icon('heroicon-o-user-group')
                    ->sort(40)
                    ->url(fn (): string => url('/admin/marketplace/beauty/stylists')),
                NavigationItem::make('Портфолио')
                    ->group('Красота и Бьюти')
                    ->icon('heroicon-o-photo')
                    ->sort(50)
                    ->url(fn (): string => url('/admin/marketplace/beauty/portfolio')),
                NavigationItem::make('Отзывы')
                    ->group('Красота и Бьюти')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->sort(60)
                    ->url(fn (): string => url('/admin/marketplace/beauty/reviews')),
                NavigationItem::make('Товары')
                    ->group('Красота и Бьюти')
                    ->icon('heroicon-o-shopping-bag')
                    ->sort(70)
                    ->url(fn (): string => url('/admin/marketplace/beauty/products')),
            ])
            ->middleware([
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesTo$this->response->class,
                \Illuminate\Session\Middleware\Start$this->session->class,
                \Illuminate\Session\Middleware\Authenticate$this->session->class,
                \Illuminate\View\Middleware\ShareErrorsFrom$this->session->class,
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
                \Filament\Http\Middleware\DisableBladeIconComponents::class,
                \Filament\Http\Middleware\DispatchServingFilament$this->event->class,
            ])
            ->authMiddleware([
                \Filament\Http\Middleware\Authenticate::class,
                \App\Http\Middleware\EnsureUserBelongsToTenant::class,
            ]);
    }

    public function boot(): void
    {
        $this->route->middleware(['web'])->group(function (): void {
            $this->route->get('/admin/marketplace/beauty/salons', function () {
                return app('router')->dispatch(Request::create('/tenant/beauty-salons', 'GET'));
            });

            $this->route->get('/admin/marketplace/beauty/salons/create', function () {
                return app('router')->dispatch(Request::create('/tenant/beauty-salons/create', 'GET'));
            });

            $this->route->get('/admin/marketplace/beauty/services', function () {
                return app('router')->dispatch(Request::create('/tenant/beauty-services', 'GET'));
            });

            $this->route->get('/admin/marketplace/beauty/services/create', function () {
                return app('router')->dispatch(Request::create('/tenant/beauty-services/create', 'GET'));
            });

            $this->route->get('/admin/marketplace/beauty/bookings', function () {
                return app('router')->dispatch(Request::create('/tenant/appointments', 'GET'));
            });

            $this->route->get('/admin/marketplace/beauty/bookings/create', function () {
                return app('router')->dispatch(Request::create('/tenant/appointments/create', 'GET'));
            });

            $this->route->get('/admin/marketplace/beauty/stylists', function () {
                return app('router')->dispatch(Request::create('/admin/marketplace/beauty/salons', 'GET'));
            });

            $this->route->get('/admin/marketplace/beauty/stylists/create', function () {
                return app('router')->dispatch(Request::create('/admin/marketplace/beauty/salons/create', 'GET'));
            });

            $this->route->get('/admin/marketplace/beauty/portfolio', function () {
                return app('router')->dispatch(Request::create('/admin/marketplace/beauty/salons', 'GET'));
            });

            $this->route->get('/admin/marketplace/beauty/products', function () {
                return app('router')->dispatch(Request::create('/admin/marketplace/beauty/services', 'GET'));
            });
        });
    }

    private function isFilamentV3ResourceCompatible(string $resourceClass): bool
    {
        if (!class_exists($resourceClass) || !is_subclass_of($resourceClass, \Filament\Resources\Resource::class)) {
            return false;
        }

        try {
            $pages = $resourceClass::getPages();
        } catch (\Throwable) {
            return false;
        }

        if ($pages === []) {
            return true;
        }

        foreach ($pages as $pageRegistration) {
            if (!is_object($pageRegistration) || !method_exists($pageRegistration, 'getPage')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, class-string<\Filament\Resources\Resource>>
     */
    private function discoverTenantResources(): array
    {
        $files = glob(base_path('app/Domains/*/Filament/**/*Resource.php')) ?: [];
        $resources = [];

        foreach ($files as $file) {
            $class = $this->resolveClassFromPath($file);

            if ($class === null) {
                continue;
            }

            if (Str::contains(class_basename($class), 'B2B')) {
                continue;
            }

            if ($this->isFilamentV3ResourceCompatible($class)) {
                $resources[] = $class;
            }
        }

        sort($resources);

        return array_values(array_unique($resources));
    }

    private function resolveClassFromPath(string $path): ?string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $appPath = base_path('app') . DIRECTORY_SEPARATOR;

        if (!str_starts_with($normalized, $appPath)) {
            return null;
        }

        $relative = Str::after($normalized, $appPath);
        $withoutExtension = preg_replace('/\.php$/', '', $relative);

        if (!is_string($withoutExtension) || $withoutExtension === '') {
            return null;
        }

        $class = 'App\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $withoutExtension);

        return class_exists($class) ? $class : null;
    }
}
