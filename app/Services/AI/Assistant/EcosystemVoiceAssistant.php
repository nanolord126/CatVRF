<?php

namespace App\Services\AI\Assistant;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EcosystemVoiceAssistant
{
    /**
     * Process Natural Language Input and Map to Actions/Queries.
     * Supports: "Show revenue for Taxi", "Calculate churn for clinic", "Suggest stock for warehouse 1".
     */
    public function processCommand(string $input): array
    {
        $input = strtolower($input);
        $response = [
            'type' => 'chat',
            'message' => "I'm sorry, I couldn't understand that command. Try 'Show revenue' or 'Run simulation'.",
            'action' => null,
            'data' => null
        ];

        // 1. Revenue/Reporting Commands
        if (Str::contains($input, ['revenue', 'sales', 'money'])) {
            $vertical = $this->extractVertical($input);
            $revenue = DB::table('global_business_forecasts')->where('vertical', $vertical)->value('revenue_today') ?? 0;
            $response['message'] = "Current revenue for " . ucfirst($vertical) . " vertical today is $" . number_format($revenue, 2);
            $response['type'] = 'report';
        }

        // 2. Navigation Commands (Filament Redirects)
        if (Str::contains($input, ['go to', 'open', 'show page'])) {
            $target = $this->mapPage($input);
            if ($target) {
                $response['message'] = "Opening " . $target['label'] . "...";
                $response['action'] = 'redirect';
                $response['url'] = $target['url'];
            }
        }

        // 3. Automation/Simulation Commands
        if (Str::contains($input, ['simulate', 'digital twin', 'run scenario'])) {
            $response['message'] = "Launching Digital Twin Simulation Dashboard. What parameters should we test?";
            $response['action'] = 'redirect';
            $response['url'] = '/admin/digital-twin-scenario-dashboard';
        }

        // 4. Security/Fraud Commands
        if (Str::contains($input, ['fraud', 'suspicious', 'alerts'])) {
            $count = DB::table('ai_fraud_detections')->where('status', 'pending')->count();
            $response['message'] = "We have {$count} pending suspicious activities flags in the AI Reputation Suite.";
            $response['type'] = 'security_alert';
            $response['action'] = 'redirect';
            $response['url'] = '/admin/ai-security-gateway-dashboard';
        }

        return $response;
    }

    private function extractVertical(string $input): string
    {
        if (Str::contains($input, 'taxi')) return 'taxi';
        if (Str::contains($input, 'food')) return 'food';
        if (Str::contains($input, ['clinic', 'medical'])) return 'clinic';
        return 'global';
    }

    private function mapPage(string $input): ?array
    {
        $map = [
            'analytics' => ['label' => 'AI Analytics', 'url' => '/admin/consumer-behavior-analytics-dashboard'],
            'pricing' => ['label' => 'Dynamic Pricing', 'url' => '/admin/ai-pricing-simulation-dashboard'],
            'logistics' => ['label' => 'AI Logistics', 'url' => '/admin/ai-logistics-communications-dashboard'],
            'dashboard' => ['label' => 'Global Dashboard', 'url' => '/admin/global-business-dashboard'],
        ];

        foreach ($map as $key => $data) {
            if (Str::contains($input, $key)) return $data;
        }

        return null;
    }
}
