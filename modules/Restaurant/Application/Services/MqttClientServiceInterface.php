<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

interface MqttClientServiceInterface
{
    public function publish(string $topic, string $message, int $qos = 0, bool $retain = false): bool;

    public function subscribe(string $topic, callable $callback, int $qos = 0): bool;

    public function loop(bool $allowSleep = true): void;

    public function disconnect(): void;

    public function isConnected(): bool;
}
