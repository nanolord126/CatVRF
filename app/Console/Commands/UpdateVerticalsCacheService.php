<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Update Verticals Cache Service Command
 *
 * Production 2026 CANON - Automated Cache Layer Migration
 *
 * Automatically updates all vertical services to use the new CacheService
 * instead of direct Cache:: facade calls. This command:
 *
 * 1. Scans all vertical service files
 * 2. Replaces Cache::remember() with $this->cacheService->rememberWithTags()
 * 3. Replaces Cache::forget() with $this->cacheService->invalidate()
 * 4. Adds CacheService injection to constructors
 * 5. Generates observers for vertical models
 *
 * @author CatVRF Team
 * @version 2026.04.18
 */
final class UpdateVerticalsCacheService extends Command
{
    protected $signature = 'cache:update-verticals {--vertical= : Specific vertical to update (e.g., beauty, food)} {--dry-run : Show changes without applying them}';

    protected $description = 'Update all vertical services to use CacheService instead of Cache facade';

    private const VERTICALS = [
        'medical', 'beauty', 'food', 'fashion', 'travel', 'auto', 'hotels',
        'electronics', 'fitness', 'sports', 'luxury', 'insurance', 'legal',
        'logistics', 'education', 'crm', 'delivery', 'payment', 'analytics',
        'consulting', 'content', 'freelance', 'event_planning', 'staff',
        'inventory', 'taxi', 'tickets', 'wallet', 'pet', 'wedding_planning',
        'veterinary', 'toys_and_games', 'advertising', 'car_rental', 'finances',
        'flowers', 'furniture', 'pharmacy', 'photography', 'short_term_rentals',
        'sports_nutrition', 'personal_development', 'home_services', 'gardening',
        'geo', 'geo_logistics', 'grocery_and_delivery', 'farm_direct', 'meat_shops',
        'office_catering', 'party_supplies', 'confectionery', 'construction_and_repair',
        'cleaning_services', 'communication', 'books_and_literature', 'collectibles',
        'hobby_and_craft', 'household_goods', 'marketplace', 'music_and_instruments',
        'vegan_products', 'art',
    ];

    public function handle(): int
    {
        $vertical = $this->option('vertical');
        $dryRun = $this->option('dry-run');

        $this->info('Cache Layer Migration Tool');
        $this->info('=========================');

        $verticalsToUpdate = $vertical ? [$vertical] : self::VERTICALS;

        foreach ($verticalsToUpdate as $verticalName) {
            $this->updateVertical($verticalName, $dryRun);
        }

        $this->newLine();
        $this->info('Migration completed successfully!');

        return Command::SUCCESS;
    }

    private function updateVertical(string $verticalName, bool $dryRun): void
    {
        $this->info("Processing vertical: {$verticalName}");

        $servicePath = $this->findVerticalServicePath($verticalName);
        
        if (!$servicePath) {
            $this->warn("  Service not found for vertical: {$verticalName}");
            return;
        }

        $this->info("  Found service: {$servicePath}");

        $content = File::get($servicePath);
        $originalContent = $content;

        // Replace Cache::remember with cacheService->rememberWithTags
        $content = $this->replaceCacheRemember($content, $verticalName);

        // Replace Cache::forget with cacheService->invalidate
        $content = $this->replaceCacheForget($content, $verticalName);

        // Replace Cache::tags with cacheService->invalidate
        $content = $this->replaceCacheTags($content, $verticalName);

        // Add CacheService injection to constructor
        $content = $this->addCacheServiceInjection($content);

        if ($content !== $originalContent) {
            if ($dryRun) {
                $this->warn("  [DRY RUN] Would update: {$servicePath}");
                $this->warn("  Changes detected but not applied");
            } else {
                File::put($servicePath, $content);
                $this->info("  ✅ Updated: {$servicePath}");
            }
        } else {
            $this->info("  No changes needed");
        }

        // Generate observer for vertical models
        $this->generateObserverForVertical($verticalName, $dryRun);
    }

    private function findVerticalServicePath(string $verticalName): ?string
    {
        $possiblePaths = [
            app_path("Domains/{$this->toPascalCase($verticalName)}/Services"),
            app_path("Domains/{$this->toPascalCase($verticalName)}/Domain/Services"),
            app_path("Services/{$this->toPascalCase($verticalName)}Service.php"),
        ];

        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                $files = File::files($path);
                foreach ($files as $file) {
                    if (str_ends_with($file->getFilename(), 'Service.php')) {
                        return $file->getPathname();
                    }
                }
            } elseif (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function replaceCacheRemember(string $content, string $verticalName): string
    {
        // Pattern: Cache::remember('key', $ttl, function() { ... })
        $pattern = '/Cache::remember\([\'"]([^\'"]+)[\'"],\s*(\d+|[^,]+),\s*function\s*\(\s*\)/';
        
        return preg_replace_callback($pattern, function ($matches) use ($verticalName) {
            $key = $matches[1];
            $ttl = $matches[2];
            
            return "\$this->cacheService->rememberWithTags(\n                    tenant()?->id,\n                    '{$key}',\n                    {$ttl},\n                    ['{$verticalName}'],\n                    function()";
        }, $content);
    }

    private function replaceCacheForget(string $content, string $verticalName): string
    {
        // Pattern: Cache::forget('key')
        $pattern = '/Cache::forget\([\'"]([^\'"]+)[\'"]\)/';
        
        return preg_replace_callback($pattern, function ($matches) use ($verticalName) {
            $key = $matches[1];
            
            return "\$this->cacheService->invalidate(tenant()?->id, ['{$verticalName}', '{$key}'])";
        }, $content);
    }

    private function replaceCacheTags(string $content, string $verticalName): string
    {
        // Pattern: Cache::tags([...])->flush()
        $pattern = '/Cache::tags\(\[([^\]]+)\]\)->flush\(\)/';
        
        return preg_replace_callback($pattern, function ($matches) use ($verticalName) {
            $tags = $matches[1];
            
            return "\$this->cacheService->invalidate(tenant()?->id, [{$tags}, '{$verticalName}'])";
        }, $content);
    }

    private function addCacheServiceInjection(string $content): string
    {
        // Check if CacheService is already injected
        if (str_contains($content, 'CacheService')) {
            return $content;
        }

        // Find constructor and add CacheService parameter
        $pattern = '/public function __construct\(([^)]*)\)/';
        
        return preg_replace_callback($pattern, function ($matches) {
            $params = $matches[1];
            $newParam = $params ? $params . ', ' : '';
            $newParam .= 'private readonly CacheService $cacheService';
            
            return "public function __construct({$newParam})";
        }, $content);
    }

    private function generateObserverForVertical(string $verticalName, bool $dryRun): void
    {
        $observerPath = app_path("Observers/{$this->toPascalCase($verticalName)}Observer.php");
        
        if (File::exists($observerPath)) {
            $this->info("  Observer already exists: {$observerPath}");
            return;
        }

        $modelClass = "App\\Domains\\{$this->toPascalCase($verticalName)}\\Models\\{$this->toPascalCase($verticalName)}";
        
        $observerTemplate = $this->getObserverTemplate($verticalName, $modelClass);

        if ($dryRun) {
            $this->warn("  [DRY RUN] Would create observer: {$observerPath}");
        } else {
            File::ensureDirectoryExists(dirname($observerPath));
            File::put($observerPath, $observerTemplate);
            $this->info("  ✅ Created observer: {$observerPath}");
        }
    }

    private function getObserverTemplate(string $verticalName, string $modelClass): string
    {
        $pascalVertical = $this->toPascalCase($verticalName);
        
        return <<<PHP
<?php declare(strict_types=1);

namespace App\Observers;

use {$modelClass};
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Log;

/**
 * {$pascalVertical} Observer
 *
 * Production 2026 CANON - Automatic Cache Invalidation
 *
 * Automatically invalidates cache when {$verticalName} entities are created, updated, or deleted.
 *
 * @author CatVRF Team
 * @version 2026.04.18
 */
final readonly class {$pascalVertical}Observer
{
    public function __construct(
        private readonly CacheService $cache,
    ) {}

    /**
     * Handle the {$pascalVertical} "created" event.
     */
    public function created({$pascalVertical} \$entity): void
    {
        \$this->invalidateRelatedCache(\$entity);
    }

    /**
     * Handle the {$pascalVertical} "updated" event.
     */
    public function updated({$pascalVertical} \$entity): void
    {
        \$this->invalidateRelatedCache(\$entity);
    }

    /**
     * Handle the {$pascalVertical} "deleted" event.
     */
    public function deleted({$pascalVertical} \$entity): void
    {
        \$this->invalidateRelatedCache(\$entity);
    }

    /**
     * Invalidate all related cache for the entity.
     */
    private function invalidateRelatedCache({$pascalVertical} \$entity): void
    {
        \$tenantId = \$entity->tenant_id ?? null;

        try {
            \$this->cache->invalidateVertical(\$tenantId, '{$verticalName}');
            
            Log::info('{$pascalVertical} cache invalidated', [
                'tenant_id' => \$tenantId,
                'entity_id' => \$entity->id,
            ]);
        } catch (\Exception \$e) {
            Log::error('Failed to invalidate {$pascalVertical} cache', [
                'tenant_id' => \$tenantId,
                'entity_id' => \$entity->id,
                'error' => \$e->getMessage(),
            ]);
        }
    }
}
PHP;
    }

    private function toPascalCase(string $string): string
    {
        return Str::studly(Str::snake($string));
    }
}
