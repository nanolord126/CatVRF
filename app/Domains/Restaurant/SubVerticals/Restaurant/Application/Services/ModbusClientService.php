<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Log\LogManager;
use Exception;

final readonly class ModbusClientService implements ModbusClientServiceInterface
{
    use WithAuditLogging;

    private $socket;
    private bool $connected = false;

    public function __construct(
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    public function connect(string $host, int $port = 502, float $timeout = 5.0): bool
    {
        try {
            $this->socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

            if (!$this->socket) {
                $this->log->error('Failed to connect to Modbus TCP device', [
                    'host' => $host,
                    'port' => $port,
                    'error' => $errstr,
                ]);
                return false;
            }

            stream_set_timeout($this->socket, $timeout);
            $this->connected = true;

            $this->log->info('Modbus TCP client connected', [
                'host' => $host,
                'port' => $port,
            ]);

            return true;
        } catch (Exception $e) {
            $this->log->error('Modbus TCP connection exception', [
                'host' => $host,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function readHoldingRegister(int $slaveId, int $address, int $quantity = 1): ?array
    {
        if (!$this->connected) {
            Log::warning('Modbus not connected');
            return null;
        }

        try {
            // Modbus TCP request frame
            $transactionId = rand(0, 65535);
            $protocolId = 0;
            $length = 6;
            $unitId = $slaveId;
            $functionCode = 0x03; // Read Holding Registers
            
            $request = pack('nnnCCnn', 
                $transactionId,
                $protocolId,
                $length,
                $unitId,
                $functionCode,
                $address,
                $quantity
            );

            fwrite($this->socket, $request);
            
            $response = fread($this->socket, 256);
            
            if (strlen($response) < 9) {
                Log::warning('Invalid Modbus response length');
                return null;
            }

            $responseData = unpack('ntrans/nprot/nlen/unit/func', substr($response, 0, 6));
            
            if ($responseData['func'] === 0x83) {
                // Error response
                $errorCode = ord($response[7]);
                Log::error('Modbus error response', [
                    'error_code' => $errorCode,
                ]);
                return null;
            }

            $byteCount = ord($response[6]);
            $values = [];
            
            for ($i = 0; $i < $byteCount; $i += 2) {
                $value = unpack('n', substr($response, 7 + $i, 2));
                $values[] = $value[1];
            }

            return $values;
        } catch (Exception $e) {
            Log::error('Modbus read error', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function writeSingleRegister(int $slaveId, int $address, int $value): bool
    {
        if (!$this->connected) {
            Log::warning('Modbus not connected');
            return false;
        }

        try {
            $transactionId = rand(0, 65535);
            $protocolId = 0;
            $length = 6;
            $unitId = $slaveId;
            $functionCode = 0x06; // Write Single Register
            
            $request = pack('nnnCCnn', 
                $transactionId,
                $protocolId,
                $length,
                $unitId,
                $functionCode,
                $address,
                $value
            );

            fwrite($this->socket, $request);
            
            $response = fread($this->socket, 256);
            
            if (strlen($response) < 12) {
                Log::warning('Invalid Modbus write response length');
                return false;
            }

            return true;
        } catch (Exception $e) {
            Log::error('Modbus write error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function disconnect(): void
    {
        if ($this->connected && $this->socket) {
            fclose($this->socket);
            $this->connected = false;
        }
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }
}
