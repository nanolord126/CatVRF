<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

interface ModbusClientServiceInterface
{
    public function connect(string $host, int $port = 502, float $timeout = 5.0): bool;

    public function readHoldingRegister(int $slaveId, int $address, int $quantity = 1): ?array;

    public function writeSingleRegister(int $slaveId, int $address, int $value): bool;

    public function disconnect(): void;

    public function isConnected(): bool;
}
