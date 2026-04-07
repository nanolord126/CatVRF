<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

final readonly class ImportService
{
    public function __construct(
        private FraudControlService $fraud,
        private RateLimiterService $rateLimiterService,
    ) {}

    public function importFromExcel(UploadedFile $file, int $tenantId, string $type = 'products', string $correlationId = ''): array
    {
        $path = $file->store('imports');

        try {
            $data = Excel::toArray(null, Storage::path($path));
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
            Storage::delete($path);
        }
    }

    public function importFromCSV(UploadedFile $file, int $tenantId, string $type = 'products'): array
    {
        $path = $file->store('imports');

        try {
            $rows = array_map('str_getcsv', file(Storage::path($path)));
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
            Storage::delete($path);
        }
    }

    private function processRow(array $row, int $tenantId, string $type): array
    {
        $validator = Validator::make($row, $this->getRules($type));

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
        $product = \App\Models\Product::create([
            'tenant_id' => $tenantId,
            'name' => $row['name'],
            'price' => (int)($row['price'] * 100),
            'sku' => $row['sku'],
            'description' => $row['description'] ?? '',
        ]);

        return $product->toArray();
    }

    private function importService(array $row, int $tenantId): array
    {
        $service = \App\Models\Service::create([
            'tenant_id' => $tenantId,
            'name' => $row['name'],
            'price' => (int)($row['price'] * 100),
            'duration_minutes' => $row['duration_minutes'] ?? 60,
        ]);

        return $service->toArray();
    }

    private function importUser(array $row, int $tenantId): array
    {
        $user = \App\Models\User::create([
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => bcrypt('random_password'),
        ]);

        return $user->toArray();
    }
}
