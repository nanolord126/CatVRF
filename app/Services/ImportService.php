<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;

final readonly class ImportService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly RateLimiterService $rateLimiterService,
        private readonly FilesystemManager $storage,
        private readonly ValidatorFactory $validator,
        private readonly AuditService $audit,
    ) {}

    public function importFromExcel(UploadedFile $file, int $tenantId, string $type = 'products', string $correlationId = ''): array
    {
        $path = $file->store('imports');

        try {
            $data = Excel::toArray(null, $this->storage->path($path));
            $records = $data[0] ?? [];

            $imported = [];
            $errors = [];

            foreach ($records as $index => $row) {
                try {
                    $imported[] = $this->processRow($row, $tenantId, $type);
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'success' => true,
                'imported' => count($imported),
                'errors' => $errors,
            ];
        } finally {
            $this->storage->delete($path);
        }
    }

    public function importFromCSV(UploadedFile $file, int $tenantId, string $type = 'products'): array
    {
        $path = $file->store('imports');

        try {
            $rows = array_map('str_getcsv', file($this->storage->path($path)));
            $header = array_shift($rows);

            $imported = [];
            $errors = [];

            foreach ($rows as $index => $values) {
                try {
                    $row = array_combine($header, $values);
                    $imported[] = $this->processRow($row, $tenantId, $type);
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $index + 2,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'success' => true,
                'imported' => count($imported),
                'errors' => $errors,
            ];
        } finally {
            $this->storage->delete($path);
        }
    }

    private function processRow(array $row, int $tenantId, string $type): array
    {
        $validator = $this->validator->make($row, $this->getRules($type));

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        return match ($type) {
            'services' => $this->importService($row, $tenantId),
            'users' => $this->importUser($row, $tenantId),
            default => $row,
        };
    }

    private function getRules(string $type): array
    {
        return match ($type) {
            'products' => [
                'name' => 'required|string',
                'price' => 'required|numeric',
                'sku' => 'required|string|unique:products',
            ],
            'services' => [
                'name' => 'required|string',
                'price' => 'required|numeric',
            ],
            'users' => [
                'name' => 'required|string',
                'email' => 'required|email|unique:users',
            ],
            default => [],
        };
    }

    private function importProduct(array $row, int $tenantId): array
    {
        $product = Product::create([
            'tenant_id' => $tenantId,
            'name' => $row['name'],
            'price' => (int) ($row['price'] * 100),
            'sku' => $row['sku'],
            'description' => $row['description'] ?? '',
        ]);

        return $product->toArray();
    }

    private function importService(array $row, int $tenantId): array
    {
        $service = Service::create([
            'tenant_id' => $tenantId,
            'name' => $row['name'],
            'price' => (int) ($row['price'] * 100),
            'duration_minutes' => $row['duration_minutes'] ?? 60,
        ]);

        return $service->toArray();
    }

    private function importUser(array $row, int $tenantId): array
    {
        $user = User::create([
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => bcrypt('random_password'),
        ]);

        return $user->toArray();
    }
}
