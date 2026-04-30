<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\BeautyMasters\Infrastructure\Models\BeautyCertificationModel;
use Modules\BeautyMasters\Infrastructure\Models\MakeupCertificationModel;
use Modules\BeautyMasters\Infrastructure\Models\BrowCertificationModel;
use Modules\BeautyMasters\Infrastructure\Models\LashCertificationModel;

final class CertificationByVerticalWidget extends ChartWidget
{
    protected static ?string $heading = 'Certifications by Vertical';

    protected function getData(): array
    {
        return [
            'labels' => ['Beauty', 'Makeup', 'Brow', 'Lash'],
            'datasets' => [
                [
                    'label' => 'Active Certifications',
                    'data' => [
                        BeautyCertificationModel::where('status', 'active')->count(),
                        MakeupCertificationModel::where('status', 'active')->count(),
                        BrowCertificationModel::where('status', 'active')->count(),
                        LashCertificationModel::where('status', 'active')->count(),
                    ],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(236, 72, 153, 0.5)',
                        'rgba(245, 158, 11, 0.5)',
                        'rgba(168, 85, 247, 0.5)',
                    ],
                    'borderColor' => [
                        'rgba(59, 130, 246, 1)',
                        'rgba(236, 72, 153, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(168, 85, 247, 1)',
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
