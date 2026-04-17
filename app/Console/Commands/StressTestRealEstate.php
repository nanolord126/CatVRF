<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Models\PropertyBooking;
use Modules\RealEstate\Services\PropertyBookingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class StressTestRealEstate extends Command
{
    protected $signature = 'stress-test:real-estate 
                            {--concurrent=50 : Number of concurrent requests}
                            {--total=1000 : Total number of bookings to create}
                            {--b2b : Include B2B bookings (15%)}';
    protected $description = 'Stress test RealEstate booking system with concurrent requests';

    public function handle(PropertyBookingService $bookingService): int
    {
        $concurrent = (int) $this->option('concurrent');
        $total = (int) $this->option('total');
        $includeB2B = $this->option('b2b');

        $this->info("Starting RealEstate stress test...");
        $this->info("Concurrent requests: {$concurrent}");
        $this->info("Total bookings: {$total}");
        $this->info("B2B included: " . ($includeB2B ? 'Yes' : 'No'));

        $startTime = microtime(true);

        $property = Property::where('status', 'active')->first();
        
        if ($property === null) {
            $this->error('No active property found. Please seed properties first.');
            return Command::FAILURE;
        }

        $this->info("Using property: {$property->id}");

        $bookingsCreated = 0;
        $errors = 0;

        $chunks = array_chunk(range(1, $total), $concurrent);

        foreach ($chunks as $chunkIndex => $chunk) {
            $this->line("Processing chunk " . ($chunkIndex + 1) . "/" . count($chunks));

            $promises = [];
            
            foreach ($chunk as $i) {
                $promises[] = $this->createBookingAsync($bookingService, $property, $includeB2B);
            }

            $results = \Illuminate\Support\Promise::all($promises);

            foreach ($results as $result) {
                if ($result['success']) {
                    $bookingsCreated++;
                } else {
                    $errors++;
                    $this->error("Error: {$result['error']}");
                }
            }

            $this->line("Chunk completed. Total: {$bookingsCreated}, Errors: {$errors}");
            
            if ($chunkIndex < count($chunks) - 1) {
                usleep(100000);
            }
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        $rps = round($total / $duration, 2);

        $this->newLine();
        $this->info("Stress test completed!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Bookings', $total],
                ['Successful', $bookingsCreated],
                ['Failed', $errors],
                ['Success Rate', round(($bookingsCreated / $total) * 100, 2) . '%'],
                ['Duration', $duration . 's'],
                ['Requests/sec', $rps],
            ]
        );

        Log::channel('audit')->info('real_estate.stress_test.completed', [
            'total' => $total,
            'successful' => $bookingsCreated,
            'failed' => $errors,
            'duration' => $duration,
            'rps' => $rps,
            'concurrent' => $concurrent,
        ]);

        return Command::SUCCESS;
    }

    private function createBookingAsync(PropertyBookingService $bookingService, Property $property, bool $includeB2B): \Illuminate\Support\Promise
    {
        return \Illuminate\Support\Promise::resolve(function () use ($bookingService, $property, $includeB2B) {
            try {
                $isB2B = $includeB2B && rand(1, 100) <= 15;
                
                $data = [
                    'property_id' => $property->id,
                    'user_id' => rand(1, 100),
                    'viewing_slot' => now()->addDays(rand(1, 30))->format('Y-m-d H:i:s'),
                    'amount' => $property->getPrice(),
                    'business_group_id' => $isB2B ? 1 : null,
                    'inn' => $isB2B ? '1234567890' : null,
                    'correlation_id' => Str::uuid()->toString(),
                ];

                $booking = $bookingService->createBooking($data);

                return ['success' => true, 'booking_id' => $booking->id];
            } catch (\Throwable $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });
    }
}
