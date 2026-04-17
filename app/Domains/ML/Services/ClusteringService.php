<?php declare(strict_types=1);

namespace App\Domains\ML\Services;

use App\Domains\ML\Models\ClusteringResult;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;
use App\Services\FraudControlService;
use App\Services\AuditService;

final readonly class ClusteringService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
    ) {}

    /**
     * Run clustering algorithm on user base
     */
    public function runClustering(string $correlationId = ''): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->fraud->check([
            'operation_type' => 'ml_clustering',
            'correlation_id' => $correlationId,
        ]);

        $this->db->transaction(function () use ($correlationId) {
            $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;

            $this->logger->info('ML clustering started', [
                'correlation_id' => $correlationId,
            ]);

            // Delete existing clusters for tenant
            ClusteringResult::where('tenant_id', $tenantId)->delete();

            // Get user behavior data for clustering
            $userBehaviors = $this->db->table('user_taste_profiles')
                ->where('tenant_id', $tenantId)
                ->get();

            if ($userBehaviors->count() < 10) {
                $this->logger->warning('Not enough data for clustering', [
                    'user_count' => $userBehaviors->count(),
                    'correlation_id' => $correlationId,
                ]);
                return;
            }

            // Simple K-means clustering implementation
            $k = min(5, max(2, (int) sqrt($userBehaviors->count() / 2)));
            $clusters = $this->performKMeansClustering($userBehaviors->toArray(), $k);

            // Save clustering results
            foreach ($clusters as $clusterId => $clusterData) {
                ClusteringResult::create([
                    'tenant_id' => $tenantId,
                    'cluster_id' => $clusterId,
                    'user_count' => count($clusterData['users']),
                    'cluster_features' => [
                        'avg_price' => $clusterData['avg_price'],
                        'top_categories' => $clusterData['top_categories'],
                        'behavioral_score_avg' => $clusterData['behavioral_score_avg'],
                    ],
                ]);
            }

            $this->audit->record(
                action: 'ml_clustering_completed',
                subjectType: ClusteringResult::class,
                subjectId: null,
                newValues: ['clusters_created' => $k],
                correlationId: $correlationId,
            );

            $this->logger->info('ML clustering completed', [
                'clusters_created' => $k,
                'users_clustered' => $userBehaviors->count(),
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Get user cluster
     */
    public function getUserCluster(int $userId): ?ClusteringResult
    {
        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;

        $userProfile = $this->db->table('user_taste_profiles')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$userProfile) {
            return null;
        }

        // Find closest cluster based on user features
        $clusters = ClusteringResult::where('tenant_id', $tenantId)->get();
        $closestCluster = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($clusters as $cluster) {
            $distance = $this->calculateClusterDistance($userProfile, $cluster->cluster_features);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestCluster = $cluster;
            }
        }

        return $closestCluster;
    }

    /**
     * Get all clusters for tenant
     */
    public function getClusters(): array
    {
        return ClusteringResult::where('tenant_id', function_exists('tenant') && tenant() ? tenant()->id : 1)
            ->get()
            ->toArray();
    }

    /**
     * Delete cluster
     */
    public function deleteCluster(int $clusterId, string $correlationId = ''): void
    {
        $correlationId ??= Str::uuid()->toString();

        ClusteringResult::where('tenant_id', function_exists('tenant') && tenant() ? tenant()->id : 1)
            ->where('cluster_id', $clusterId)
            ->delete();

        $this->logger->info('ML cluster deleted', [
            'cluster_id' => $clusterId,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Perform K-means clustering algorithm
     */
    private function performKMeansClustering(array $users, int $k): array
    {
        $clusters = [];
        $maxIterations = 100;
        $convergenceThreshold = 0.01;

        // Initialize centroids randomly
        $centroids = [];
        for ($i = 0; $i < $k; $i++) {
            $randomUser = $users[array_rand($users)];
            $centroids[$i] = [
                'price' => $randomUser['price_range']['max'] ?? 50000,
                'behavioral_score' => $randomUser['behavioral_score'] ?? 0.5,
            ];
        }

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $newClusters = array_fill(0, $k, ['users' => [], 'prices' => [], 'scores' => []]);

            // Assign users to nearest centroid
            foreach ($users as $user) {
                $minDistance = PHP_FLOAT_MAX;
                $assignedCluster = 0;

                foreach ($centroids as $clusterId => $centroid) {
                    $distance = sqrt(
                        pow(($user['price_range']['max'] ?? 50000) - $centroid['price'], 2) +
                        pow(($user['behavioral_score'] ?? 0.5) - $centroid['behavioral_score'], 2)
                    );

                    if ($distance < $minDistance) {
                        $minDistance = $distance;
                        $assignedCluster = $clusterId;
                    }
                }

                $newClusters[$assignedCluster]['users'][] = $user;
                $newClusters[$assignedCluster]['prices'][] = $user['price_range']['max'] ?? 50000;
                $newClusters[$assignedCluster]['scores'][] = $user['behavioral_score'] ?? 0.5;
            }

            // Recalculate centroids
            $newCentroids = [];
            foreach ($newClusters as $clusterId => $cluster) {
                if (!empty($cluster['prices'])) {
                    $newCentroids[$clusterId] = [
                        'price' => array_sum($cluster['prices']) / count($cluster['prices']),
                        'behavioral_score' => array_sum($cluster['scores']) / count($cluster['scores']),
                    ];
                } else {
                    $newCentroids[$clusterId] = $centroids[$clusterId];
                }
            }

            // Check for convergence
            $maxCentroidShift = 0;
            foreach ($centroids as $clusterId => $centroid) {
                $shift = abs($centroid['price'] - $newCentroids[$clusterId]['price']) +
                         abs($centroid['behavioral_score'] - $newCentroids[$clusterId]['behavioral_score']);
                $maxCentroidShift = max($maxCentroidShift, $shift);
            }

            $centroids = $newCentroids;
            $clusters = $newClusters;

            if ($maxCentroidShift < $convergenceThreshold) {
                break;
            }
        }

        // Prepare cluster results
        $results = [];
        foreach ($clusters as $clusterId => $cluster) {
            $categoryCounts = [];
            foreach ($cluster['users'] as $user) {
                foreach ($user['category_preferences'] ?? [] as $catId => $count) {
                    $categoryCounts[$catId] = ($categoryCounts[$catId] ?? 0) + $count;
                }
            }
            arsort($categoryCounts);
            $topCategories = array_slice(array_keys($categoryCounts), 0, 5);

            $results[$clusterId] = [
                'users' => $cluster['users'],
                'avg_price' => !empty($cluster['prices']) ? array_sum($cluster['prices']) / count($cluster['prices']) : 0,
                'top_categories' => $topCategories,
                'behavioral_score_avg' => !empty($cluster['scores']) ? array_sum($cluster['scores']) / count($cluster['scores']) : 0,
            ];
        }

        return $results;
    }

    /**
     * Calculate distance between user profile and cluster features
     */
    private function calculateClusterDistance(object $userProfile, array $clusterFeatures): float
    {
        $userPrice = $userProfile->price_range['max'] ?? 50000;
        $userScore = $userProfile->behavioral_score ?? 0.5;
        $clusterPrice = $clusterFeatures['avg_price'] ?? 50000;
        $clusterScore = $clusterFeatures['behavioral_score_avg'] ?? 0.5;

        return sqrt(
            pow($userPrice - $clusterPrice, 2) +
            pow($userScore - $clusterScore, 2)
        );
    }
}
