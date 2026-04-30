<?php

declare(strict_types=1);

namespace App\Filament\Resources\DriftMonitoringResource\Pages;

use App\Filament\Resources\DriftMonitoringResource;
use Filament\Pages\Page;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\Facades\DB;

class DriftDashboard extends Page
{
    protected static string $resource = DriftMonitoringResource::class;

    protected static string $view = 'filament.resources.drift-monitoring-resource.pages.drift-dashboard';

    public function getViewData(): array
    {
        return [
            'overall_stats' => $this->getOverallStats(),
            'drift_by_model' => $this->getDriftByModel(),
            'drift_by_vertical' => $this->getDriftByVertical(),
            'recent_alerts' => $this->getRecentAlerts(),
            'top_drifting_features' => $this->getTopDriftingFeatures(),
        ];
    }

    private function getOverallStats(): array
    {
        // In production, this would query ClickHouse
        return [
            'total_models' => 12,
            'critical_drift' => 2,
            'warning_drift' => 3,
            'normal' => 7,
            'last_analysis' => now()->subHours(2)->toDateTimeString(),
        ];
    }

    private function getDriftByModel(): array
    {
        return [
            ['model' => 'Behavioral Biometrics', 'drift_score' => 0.15, 'status' => 'warning'],
            ['model' => 'Fraud ML Ensemble', 'drift_score' => 0.08, 'status' => 'normal'],
            ['model' => 'Insider Threat', 'drift_score' => 0.32, 'status' => 'critical'],
            ['model' => 'VPN/Proxy Detection', 'drift_score' => 0.05, 'status' => 'normal'],
        ];
    }

    private function getDriftByVertical(): array
    {
        return [
            ['vertical' => 'Medical', 'drift_score' => 0.12, 'status' => 'warning'],
            ['vertical' => 'Payment', 'drift_score' => 0.06, 'status' => 'normal'],
            ['vertical' => 'Taxi', 'drift_score' => 0.18, 'status' => 'warning'],
            ['vertical' => 'Hotels', 'drift_score' => 0.04, 'status' => 'normal'],
            ['vertical' => 'Food', 'drift_score' => 0.09, 'status' => 'normal'],
        ];
    }

    private function getRecentAlerts(): array
    {
        return [
            [
                'model' => 'Insider Threat',
                'vertical' => 'Taxi',
                'severity' => 'critical',
                'message' => 'Critical drift detected - accuracy decay 9.2%',
                'timestamp' => now()->subHours(1)->toDateTimeString(),
            ],
            [
                'model' => 'Behavioral Biometrics',
                'vertical' => 'Medical',
                'severity' => 'warning',
                'message' => 'Data drift detected - keystroke timing shifted',
                'timestamp' => now()->subHours(3)->toDateTimeString(),
            ],
        ];
    }

    private function getTopDriftingFeatures(): array
    {
        return [
            ['feature' => 'keystroke_timing', 'model' => 'Behavioral Biometrics', 'psi' => 0.28],
            ['feature' => 'transaction_velocity', 'model' => 'Fraud ML Ensemble', 'psi' => 0.22],
            ['feature' => 'access_pattern', 'model' => 'Insider Threat', 'psi' => 0.31],
            ['feature' => 'geo_risk_score', 'model' => 'Fraud ML Ensemble', 'psi' => 0.18],
            ['feature' => 'mouse_velocity', 'model' => 'Behavioral Biometrics', 'psi' => 0.15],
        ];
    }
}
