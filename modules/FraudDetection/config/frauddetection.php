<?php

declare(strict_types=1);

return [
    // Порог срабатывания фрод-системы. Если score >= threshold, транзакция блокируется.
    'threshold' => (float) env('FRAUD_DETECTION_THRESHOLD', 0.85),

    // Эндпоинт ML-сервиса для скоринга
    'ml_service_endpoint' => env('FRAUD_ML_SERVICE_ENDPOINT', 'http://127.0.0.1:8001/score'),

    // Включить/выключить моковый сервис
    'use_mock_service' => env('FRAUD_USE_MOCK_SERVICE', true),
];
