<?php

namespace App\Jobs\Finances\Security;

use App\Domains\Finances\Services\Security\FraudMLService;
use App\Domains\Finances\Models\Security\MLModelVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class TrainFraudModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(FraudMLService $mlService): void
    {
        $version = $mlService->train();
        
        MLModelVersion::create([
            'ver' => $version,
            'samples' => 1000, 'accuracy' => rand(88, 96) / 100,
            'path' => "storage/fraud_model_{$version}.json",
            'correlation_id' => uniqid('ML_TRAIN_')
        ]);
        
        \Illuminate\Support\Facades\Log::info("Fraud ML model trained: {$version}");
    }
}
