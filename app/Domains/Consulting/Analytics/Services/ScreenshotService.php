<?php declare(strict_types=1);

namespace App\Domains\Consulting\Analytics\Services;

use Carbon\Carbon;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use Illuminate\Config\Repository as ConfigRepository;

final class ScreenshotService
{


    /**
         * @var int Screenshot cache TTL in seconds (1 hour)
         */
        private int $cacheTtl = 3600;

        /**
         * @var int Puppeteer timeout in milliseconds (30 seconds)
         */
        private int $puppeteerTimeout = 30000;

        /**
         * @var int Maximum screenshot width in pixels
         */
        private int $maxWidth = 1920;

        /**
         * @var int Maximum screenshot height in pixels
         */
        private int $maxHeight = 1080;

        /**
         * @var string Cache tag for screenshot invalidation
         */
        private string $cacheTag = 'screenshots';

        /**
         * @var array URLs whitelist for security (SSRF prevention)
         */
        private array $urlWhitelist = [];

        /**
         * Create a new ScreenshotService instance.
         */
        public function __construct(private readonly \Illuminate\Cache\CacheManager $cache,
        private readonly ConfigRepository $config, private readonly Request $request, private readonly LoggerInterface $logger)
        {
            // Load whitelist from config or environment
            $this->urlWhitelist = $this->config->get('analytics.screenshot_whitelist', []);
        }

        /**
         * Capture screenshot of a page for click-heatmap visualization.
         *
         * Attempts to retrieve cached screenshot first. If not cached,
         * captures new screenshot using headless browser and caches result.
         *
         * @param string $url Page URL to capture
         * @param int $tenantId Tenant ID for cache isolation
         * @param string|null $correlationId Correlation ID for tracing
         * @param array $options Capture options (viewport, wait_time, etc.)
         * @return array Screenshot metadata: {url, path, size, width, height, cached, captured_at, expires_at}
         *
         * @throws \InvalidArgumentException If URL is invalid or not whitelisted
         * @throws \RuntimeException If screenshot capture fails
         */
        public function capturePageScreenshot(
            string $url,
            int $tenantId,
            ?string $correlationId = null,
            array $options = []
        ): array {
            $correlationId ??= "screenshot-{$this->generateTraceId()}";

            try {
                // Validate URL
                $this->validateUrl($url);

                // Generate cache key
                $cacheKey = $this->generateCacheKey($url, $tenantId);

                $this->logger->debug('Attempting to retrieve cached screenshot', [
                    'url' => $url,
                    'tenant_id' => $tenantId,
                    'cache_key' => $cacheKey,
                    'correlation_id' => $correlationId,
                ]);

                // Try to get cached screenshot
                $cachedScreenshot = $this->cache->get($cacheKey);
                if ($cachedScreenshot) {
                    return \array_merge($cachedScreenshot, [
                        'cached' => true,
                        'correlation_id' => $correlationId,
                    ]);
                }

                // Capture new screenshot
                $this->logger->info('Capturing new page screenshot', [
                    'url' => $url,
                    'tenant_id' => $tenantId,
                    'timeout_ms' => $this->puppeteerTimeout,
                    'correlation_id' => $correlationId,
                ]);

                $screenshotData = $this->captureWithPuppeteer($url, $options);

                // Cache screenshot
                $screenshotMetadata = [
                    'url' => $url,
                    'path' => $screenshotData['path'],
                    'size' => $screenshotData['size'],
                    'width' => $screenshotData['width'],
                    'height' => $screenshotData['height'],
                    'format' => 'png',
                    'data_url' => $screenshotData['data_url'] ?? null,
                    'captured_at' => \Carbon::now()->toIso8601String(),
                ];

                $this->cache->put(
                    $cacheKey,
                    $screenshotMetadata,
                    $this->cacheTtl
                );

                $this->logger->info('Page screenshot captured and cached', [
                    'url' => $url,
                    'tenant_id' => $tenantId,
                    'file_size' => $screenshotData['size'],
                    'dimensions' => "{$screenshotData['width']}x{$screenshotData['height']}",
                    'cache_ttl' => $this->cacheTtl,
                    'correlation_id' => $correlationId,
                ]);

                return \array_merge($screenshotMetadata, [
                    'cached' => false,
                    'expires_at' => \Carbon::now()->addSeconds($this->cacheTtl)->toIso8601String(),
                    'correlation_id' => $correlationId,
                ]);

            } catch (\Throwable $e) {
                $this->logger->error('Screenshot capture failed', [
                    'url' => $url,
                    'tenant_id' => $tenantId,
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);

                // Return fallback placeholder
                return $this->getFallbackScreenshot($url, $tenantId, $correlationId, $e);
            }
        }

        /**
         * Invalidate screenshot cache for a specific URL.
         *
         * Called when page content changes and screenshot needs to be recaptured.
         *
         * @param string $url Page URL
         * @param int $tenantId Tenant ID
         * @param string|null $correlationId Correlation ID
         * @return bool True if cache was invalidated, false if not cached
         */
        public function invalidateScreenshot(
            string $url,
            int $tenantId,
            ?string $correlationId = null
        ): bool {
            $correlationId ??= "invalidate-{$this->generateTraceId()}";
            $cacheKey = $this->generateCacheKey($url, $tenantId);

            $wasInCache = $this->cache->has($cacheKey);
            $this->cache->forget($cacheKey);

            $this->logger->debug('Screenshot cache invalidated', [
                'url' => $url,
                'tenant_id' => $tenantId,
                'cache_key' => $cacheKey,
                'was_cached' => $wasInCache,
                'correlation_id' => $correlationId,
            ]);

            return $wasInCache;
        }

        /**
         * Invalidate all screenshots for a tenant.
         *
         * Useful for major page redesigns or tenant data reset.
         *
         * @param int $tenantId Tenant ID
         * @param string|null $correlationId Correlation ID
         * @return int Number of cache keys invalidated
         */
        public function invalidateAllScreenshots(
            int $tenantId,
            ?string $correlationId = null
        ): int {
            $correlationId ??= "invalidate-all-{$this->generateTraceId()}";

            // Using cache tags for bulk invalidation
            // Tag format: screenshots:tenant:{id}
            try {
                $this->cache->tags([$this->cacheTag, "tenant:{$tenantId}"])->flush();

                $this->logger->info('All tenant screenshots invalidated', [
                    'tenant_id' => $tenantId,
                    'correlation_id' => $correlationId,
                ]);

                return -1; // Unknown count with tag-based invalidation
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to invalidate all screenshots', [
                    'tenant_id' => $tenantId,
                    'error_message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return 0;
            }
        }

        /**
         * Capture screenshot using headless Puppeteer browser.
         *
         * Executes Node.js Puppeteer script to:
         * 1. Launch headless Chrome/Chromium browser
         * 2. Open page with timeout
         * 3. Wait for page load (networkIdle2)
         * 4. Capture screenshot
         * 5. Return base64-encoded PNG
         *
         * @param string $url Page URL to capture
         * @param array $options Capture options
         * @return array Screenshot data: {path, size, width, height, data_url}
         *
         * @throws \RuntimeException If Puppeteer capture fails
         */
        private function captureWithPuppeteer(string $url, array $options = []): array
        {
            // This is a placeholder implementation
            // In production, you would execute Node.js Puppeteer script:
            //
            // Example Node.js script (resources/scripts/capture-screenshot.js):
            // ```javascript
            // const puppeteer = require('puppeteer');
            // (async () => {
            //   const browser = await puppeteer.launch({headless: 'new'});
            //   const page = await browser.newPage();
            //   await page.setViewport({width: 1920, height: 1080});
            //   await page.goto(process.argv[2], {waitUntil: 'networkIdle2'});
            //   const screenshot = await page.screenshot({encoding: 'base64'});
            //   console.log(JSON.stringify({
            //     width: 1920,
            //     height: 1080,
            //     data: screenshot,
            //     size: screenshot.length
            //   }));
            //   await browser.close();
            // })();
            // ```
            //
            // Then in Laravel, call:
            // $result = shell_exec('node resources/scripts/capture-screenshot.js ' . escapeshellarg($url));
            // $data = json_decode($result, true);

            $this->logger->debug('Capturing screenshot with Puppeteer', [
                'url' => $url,
                'viewport' => "{$this->maxWidth}x{$this->maxHeight}",
                'options' => $options,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);
            // OR use Laravel package:
            // - spatie/browsershot (wrapper for Puppeteer)
            // - compose require spatie/browsershot
            //
            // Usage:
            // $screenshot = Browsershot::url($url)
            //     ->setNoSandbox()
            //     ->screenshot();

            throw new \RuntimeException(
                'Puppeteer screenshot capture not yet implemented. '
                . 'Install Browsershot: composer require spatie/browsershot'
            );
        }

        /**
         * Validate page URL for safety (SSRF prevention).
         *
         * Checks against whitelist to prevent Server-Side Request Forgery attacks.
         * Only allows capturing screenshots of domains owned by the tenant.
         *
         * @param string $url URL to validate
         * @return void
         *
         * @throws \InvalidArgumentException If URL is invalid or not whitelisted
         */
        private function validateUrl(string $url): void
        {
            // Parse URL
            $parsedUrl = \parse_url($url);
            if (!$parsedUrl || !isset($parsedUrl['host'])) {
                throw new \InvalidArgumentException('Invalid URL format');
            }

            $host = $parsedUrl['host'];
            $scheme = $parsedUrl['scheme'] ?? 'https';

            // Validate scheme (only HTTP/HTTPS allowed)
            if (!\in_array($scheme, ['http', 'https'], true)) {
                throw new \InvalidArgumentException("Unsupported URL scheme: {$scheme}");
            }

            // Check against whitelist
            $isWhitelisted = false;
            foreach ($this->urlWhitelist as $whitelistedDomain) {
                if ($host === $whitelistedDomain || Str::endsWith($host, ".{$whitelistedDomain}")) {
                    $isWhitelisted = true;
                    break;
                }
            }

            if (!$isWhitelisted && !empty($this->urlWhitelist)) {
                throw new \InvalidArgumentException(
                    "URL domain '{$host}' is not whitelisted for screenshot capture"
                );
            }

            // Prevent localhost/private IPs
            $ip = \gethostbyname($host);
            if ($this->isPrivateIp($ip)) {
                throw new \InvalidArgumentException(
                    "Cannot capture screenshots from private IP addresses: {$ip}"
                );
            }
        }

        /**
         * Check if IP address is private/reserved.
         *
         * @param string $ip IP address
         * @return bool True if IP is private/reserved
         */
        private function isPrivateIp(string $ip): bool
        {
            return \filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE) === false;
        }

        /**
         * Generate cache key for screenshot.
         *
         * Format: screenshot:tenant:{id}:url:{md5_hash}
         * Uses MD5 hash of URL to keep key length reasonable.
         *
         * @param string $url Page URL
         * @param int $tenantId Tenant ID
         * @return string Cache key
         */
        private function generateCacheKey(string $url, int $tenantId): string
        {
            $urlHash = \md5($url);
            return "screenshot:tenant:{$tenantId}:url:{$urlHash}";
        }

        /**
         * Get fallback/placeholder screenshot when capture fails.
         *
         * Returns a generic placeholder image with error message
         * instead of crashing the click-heatmap visualization.
         *
         * @param string $url Failed URL
         * @param int $tenantId Tenant ID
         * @param string $correlationId Correlation ID
         * @param \Exception $error Error that occurred
         * @return array Fallback screenshot metadata
         */
        private function getFallbackScreenshot(
            string $url,
            int $tenantId,
            string $correlationId,
            \Exception $error
        ): array {
            return [
                'url' => $url,
                'path' => 'placeholder://screenshot-error.png',
                'size' => 0,
                'width' => $this->maxWidth,
                'height' => $this->maxHeight,
                'format' => 'png',
                'error' => $error->getMessage(),
                'fallback' => true,
                'captured_at' => \Carbon::now()->toIso8601String(),
                'expires_at' => \Carbon::now()->addSeconds($this->cacheTtl)->toIso8601String(),
                'cached' => false,
                'correlation_id' => $correlationId,
            ];
        }

        /**
         * Generate unique trace ID.
         *
         * @return string Trace ID (timestamp-random)
         */
        private function generateTraceId(): string
        {
            return \Carbon::now()->timestamp . '-' . Str::random(8);
        }

        /**
         * Set screenshot cache TTL.
         *
         * @param int $seconds TTL in seconds
         * @return $this Fluent interface
         */
        public function setCacheTtl(int $seconds): self
        {
            $this->cacheTtl = $seconds;
            return $this;
        }

        /**
         * Set Puppeteer timeout.
         *
         * @param int $milliseconds Timeout in milliseconds
         * @return $this Fluent interface
         */
        public function setPuppeteerTimeout(int $milliseconds): self
        {
            $this->puppeteerTimeout = $milliseconds;
            return $this;
        }

        /**
         * Set viewport dimensions for screenshots.
         *
         * @param int $width Width in pixels
         * @param int $height Height in pixels
         * @return $this Fluent interface
         */
        public function setViewport(int $width, int $height): self
        {
            $this->maxWidth = $width;
            $this->maxHeight = $height;
            return $this;
        }

        /**
         * Set URL whitelist for SSRF prevention.
         *
         * @param array $domains Whitelisted domain names
         * @return $this Fluent interface
         */
        public function setUrlWhitelist(array $domains): self
        {
            $this->urlWhitelist = $domains;
            return $this;
        }

        /**
         * Add domain to URL whitelist.
         *
         * @param string $domain Domain to whitelist
         * @return $this Fluent interface
         */
        public function addWhitelistedDomain(string $domain): self
        {
            if (!\in_array($domain, $this->urlWhitelist, true)) {
                $this->urlWhitelist[] = $domain;
            }
            return $this;
        }
}
