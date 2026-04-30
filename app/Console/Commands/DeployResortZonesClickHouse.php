<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Factory as HttpFactory;

final class DeployResortZonesClickHouse extends Command
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
        parent::__construct();
    }
    protected $signature = 'clickhouse:deploy-resort-zones';

    protected $description = 'Deploy resort zones schema to ClickHouse';

    public function handle(): int
    {
        $this->info('Deploying resort zones schema to ClickHouse...');

        $sqlFile = database_path('clickhouse/migrations/2024_01_01_000001_create_resort_zones.sql');

        if (! file_exists($sqlFile)) {
            $this->error('SQL file not found: '.$sqlFile);

            return self::FAILURE;
        }

        $sql = file_get_contents($sqlFile);

        // Get ClickHouse configuration
        $host = config('clickhouse.host', 'localhost');
        $port = config('clickHouse.port', 8123);
        $database = config('clickhouse.database', 'default');
        $user = config('clickhouse.username', 'default');
        $password = config('clickhouse.password', '');

        $this->info("Connecting to ClickHouse at {$host}:{$port}...");

        try {
            // Execute SQL via HTTP interface
            $response = $this->http->withBasicAuth($user, $password)
                ->post("http://{$host}:{$port}/", [
                    'query' => $sql,
                    'database' => $database,
                ]);

            if ($response->failed()) {
                $this->error('Failed to execute ClickHouse migration: '.$response->body());

                return self::FAILURE;
            }

            $this->info('✓ Resort zones schema deployed successfully');

            // Verify tables exist
            $tables = ['ch_resort_zones', 'ch_resort_zone_polygons', 'ch_resort_coastline'];
            foreach ($tables as $table) {
                $checkResponse = $this->http->withBasicAuth($user, $password)
                    ->post("http://{$host}:{$port}/", [
                        'query' => "EXISTS TABLE {$database}.{$table}",
                        'database' => $database,
                    ]);

                if ($checkResponse->body() === '1') {
                    $this->info("✓ Table {$table} exists");
                } else {
                    $this->warn("Table {$table} not found");
                }
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
