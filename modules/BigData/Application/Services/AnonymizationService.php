<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Services;

use Modules\BigData\Domain\Enums\AnonymizationMethod;
use Modules\BigData\Domain\Interfaces\AnonymizationInterface;

/**
 * PII anonymization, pseudonymization, differential privacy for BigData.
 * Ensures GDPR/152-FZ compliance for ML features and data exports.
 */
final class AnonymizationService implements AnonymizationInterface
{
    /** Pseudonymization key cache per context */
    private array $pseudonymMap = [];

    public function maskPII(array $data, array $piiFields): array
    {
        foreach ($piiFields as $field) {
            if (!isset($data[$field])) {
                continue;
            }
            $value = (string) $data[$field];
            $len = mb_strlen($value);

            if ($len <= 2) {
                $data[$field] = str_repeat('*', $len);
            } elseif (str_contains($field, 'email')) {
                $parts = explode('@', $value);
                $data[$field] = str_repeat('*', max(1, mb_strlen($parts[0]) - 2)) . '***@' . ($parts[1] ?? '***');
            } elseif (str_contains($field, 'phone')) {
                $data[$field] = str_repeat('*', $len - 4) . substr($value, -4);
            } elseif (str_contains($field, 'ip')) {
                $segments = explode('.', $value);
                if (count($segments) === 4) {
                    $data[$field] = $segments[0] . '.' . $segments[1] . '.*.*';
                } else {
                    $data[$field] = str_repeat('*', $len);
                }
            } elseif (str_contains($field, 'card') || str_contains($field, 'token')) {
                $data[$field] = str_repeat('*', max(0, $len - 4)) . substr($value, -4);
            } else {
                $data[$field] = str_repeat('*', $len);
            }
        }
        return $data;
    }

    public function anonymizeForML(array $features, AnonymizationMethod $method = AnonymizationMethod::DifferentialPrivacy): array
    {
        $piiKeys = ['user_id', 'email', 'phone', 'ip_address', 'device_id', 'session_id', 'name'];

        foreach ($features as $key => $value) {
            if (in_array($key, $piiKeys, true)) {
                $features[$key] = $method === AnonymizationMethod::Pseudonymization
                    ? $this->pseudonymize((string) $value)
                    : $this->hash((string) $value);
            } elseif (is_numeric($value) && $method === AnonymizationMethod::DifferentialPrivacy) {
                $features[$key] = $this->addDPNoise((float) $value, epsilon: 1.0, sensitivity: $this->inferSensitivity($key));
            }
        }
        return $features;
    }

    public function pseudonymize(string $value, string $context = 'default'): string
    {
        $cacheKey = $context . ':' . $value;
        if (!isset($this->pseudonymMap[$cacheKey])) {
            $this->pseudonymMap[$cacheKey] = 'pseudo_' . hash('xxh128', $context . ':' . $value . ':' . config('app.key'));
        }
        return $this->pseudonymMap[$cacheKey];
    }

    public function depseudonymize(string $pseudonym, string $context = 'default'): string
    {
        $reverseMap = array_flip(array_filter(
            $this->pseudonymMap,
            fn(string $k) => str_starts_with($k, $context . ':'),
        ));
        return $reverseMap[$pseudonym] ?? throw new \RuntimeException("Cannot reverse pseudonym: {$pseudonym}");
    }

    public function applyKAnonymity(array $dataset, int $k = 5, array $quasiIdentifiers = []): array
    {
        if (empty($quasiIdentifiers) || empty($dataset)) {
            return $dataset;
        }

        // Group by quasi-identifier combinations, generalize until each group >= k
        $groups = [];
        foreach ($dataset as $row) {
            $key = implode('|', array_map(fn($qi) => (string) ($row[$qi] ?? ''), $quasiIdentifiers));
            $groups[$key][] = $row;
        }

        $result = [];
        foreach ($groups as $group) {
            if (count($group) < $k) {
                // Generalize quasi-identifiers: replace specific values with ranges
                $generalized = $group;
                foreach ($quasiIdentifiers as $qi) {
                    $values = array_filter(array_map(fn($r) => $r[$qi] ?? null, $generalized));
                    if (!empty($values) && is_numeric(reset($values))) {
                        $min = min($values);
                        $max = max($values);
                        foreach ($generalized as &$row) {
                            $row[$qi] = "[{$min}-{$max}]";
                        }
                    } else {
                        foreach ($generalized as &$row) {
                            $row[$qi] = '*';
                        }
                    }
                }
                $result = array_merge($result, $generalized);
            } else {
                $result = array_merge($result, $group);
            }
        }
        return $result;
    }

    public function addDPNoise(float $value, float $epsilon = 1.0, float $sensitivity = 1.0): float
    {
        // Laplace mechanism: noise ~ Laplace(0, sensitivity/epsilon)
        $scale = $sensitivity / max($epsilon, 0.01);
        $u = random_int(-PHP_INT_MAX, PHP_INT_MAX) / PHP_INT_MAX;
        $noise = -$scale * ($u >= 0 ? log(max(1 - $u, 1e-15)) : -log(max(abs($u), 1e-15)));
        return round($value + $noise, 6);
    }

    public function hash(string $value, string $algorithm = 'sha256'): string
    {
        return hash($algorithm, $value . config('app.key'));
    }

    public function tokenize(string $value, string $domain = 'default'): string
    {
        return 'tok_' . $domain . '_' . hash('xxh128', $value . ':' . $domain . ':' . config('app.key') . ':' . time());
    }

    /** Infer sensitivity of a numeric feature for DP noise calibration */
    private function inferSensitivity(string $featureName): float
    {
        return match (true) {
            str_contains($featureName, 'amount'), str_contains($featureName, 'price'), str_contains($featureName, 'gmv') => 1000.0,
            str_contains($featureName, 'count'), str_contains($featureName, 'quantity') => 10.0,
            str_contains($featureName, 'score'), str_contains($featureName, 'rating') => 1.0,
            default => 0.1,
        };
    }
}
