<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 100%;
            padding: 20px;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #3B82F6;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 28px;
            color: #1F2937;
            margin-bottom: 5px;
        }

        .header .subtitle {
            font-size: 12px;
            color: #6B7280;
        }

        /* Info Grid */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            background: #F3F4F6;
            border: 1px solid #E5E7EB;
        }

        .info-item {
            display: table-cell;
            padding: 12px;
            border-right: 1px solid #E5E7EB;
            width: 25%;
        }

        .info-item:last-child {
            border-right: none;
        }

        .info-label {
            font-size: 11px;
            color: #6B7280;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 14px;
            color: #1F2937;
            font-weight: 500;
        }

        /* Chart Image */
        .chart-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .chart-section h2 {
            font-size: 18px;
            color: #1F2937;
            margin-bottom: 15px;
            border-bottom: 2px solid #3B82F6;
            padding-bottom: 10px;
        }

        .chart-image {
            width: 100%;
            max-height: 400px;
            border: 1px solid #E5E7EB;
            border-radius: 4px;
            background: white;
        }

        /* Metadata */
        .metadata-section {
            margin-bottom: 30px;
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            padding: 15px;
            border-radius: 4px;
        }

        .metadata-section h3 {
            font-size: 14px;
            color: #1F2937;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .metadata-table {
            width: 100%;
            font-size: 12px;
            color: #374151;
        }

        .metadata-table tr {
            border-bottom: 1px solid #E5E7EB;
        }

        .metadata-table td {
            padding: 8px 0;
            padding-right: 15px;
        }

        .metadata-table td:first-child {
            font-weight: bold;
            color: #1F2937;
            width: 30%;
        }

        .metadata-table tr:last-child {
            border-bottom: none;
        }

        /* Chart Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 11px;
        }

        .data-table thead {
            background: #3B82F6;
            color: white;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #E5E7EB;
            padding: 10px;
            text-align: left;
        }

        .data-table thead th {
            background: #3B82F6;
            color: white;
            border: none;
            padding: 10px;
            text-align: left;
        }

        .data-table tbody tr:nth-child(even) {
            background: #F9FAFB;
        }

        /* Description */
        .description {
            margin-bottom: 20px;
            padding: 15px;
            background: #EFF6FF;
            border-left: 4px solid #3B82F6;
            border-radius: 2px;
            color: #1E40AF;
            font-size: 12px;
            line-height: 1.5;
        }

        /* Footer */
        .footer {
            border-top: 1px solid #E5E7EB;
            margin-top: 40px;
            padding-top: 15px;
            font-size: 10px;
            color: #6B7280;
            text-align: center;
        }

        .correlation-id {
            font-family: 'Courier New', monospace;
            background: #F3F4F6;
            padding: 2px 4px;
            border-radius: 2px;
        }

        /* Page Break */
        .page-break {
            page-break-after: always;
        }

        /* Responsive */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $title }}</h1>
            <div class="subtitle">{{ $tenant_name }} • Аналитический отчёт</div>
        </div>

        <!-- Description -->
        @if ($description)
            <div class="description">
                {{ $description }}
            </div>
        @endif

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Дата создания</div>
                <div class="info-value">{{ $generated_at }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Организация</div>
                <div class="info-value">{{ $tenant_name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Correlation ID</div>
                <div class="info-value"><span class="correlation-id">{{ substr($correlation_id, 0, 12) }}...</span></div>
            </div>
            <div class="info-item">
                <div class="info-label">Версия отчёта</div>
                <div class="info-value">1.0</div>
            </div>
        </div>

        <!-- Chart Image -->
        @if ($chart_image)
            <div class="chart-section">
                <h2>График</h2>
                <img src="{{ $chart_image }}" alt="Chart" class="chart-image">
            </div>
        @endif

        <!-- Chart Data & Metadata -->
        @if (!empty($metadata))
            <div class="metadata-section">
                <h3>Метаданные</h3>
                <table class="metadata-table">
                    <tr>
                        <td>Всего событий</td>
                        <td>{{ number_format($metadata['total'] ?? 0, 0, ',', ' ') }}</td>
                    </tr>
                    <tr>
                        <td>Уникальные пользователи</td>
                        <td>{{ number_format($metadata['users'] ?? 0, 0, ',', ' ') }}</td>
                    </tr>
                    <tr>
                        <td>Период анализа</td>
                        <td>{{ $metadata['period'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Точность кэша</td>
                        <td>{{ $metadata['cache_hit'] ?? 'unknown' }}</td>
                    </tr>
                    @if (!empty($metadata['additional']))
                        @foreach ($metadata['additional'] as $key => $value)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                <td>{{ $value }}</td>
                            </tr>
                        @endforeach
                    @endif
                </table>
            </div>
        @endif

        <!-- Chart Data Table (если есть) -->
        @if (!empty($chart_data['data']))
            <div style="margin-top: 30px;">
                <h3 style="font-size: 14px; margin-bottom: 15px; border-bottom: 2px solid #3B82F6; padding-bottom: 10px;">Детальные данные</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Период</th>
                            @if (!empty($chart_data['labels']))
                                @foreach (array_slice($chart_data['labels'], 0, 3) as $label)
                                    <th>{{ substr($label, 0, 20) }}</th>
                                @endforeach
                            @endif
                            <th>...</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($chart_data['datasets']))
                            @foreach ($chart_data['datasets'] as $dataset)
                                <tr>
                                    <td><strong>{{ $dataset['label'] ?? 'Dataset' }}</strong></td>
                                    @foreach (array_slice($dataset['data'] ?? [], 0, 3) as $value)
                                        <td>{{ is_array($value) ? $value['y'] ?? $value : $value }}</td>
                                    @endforeach
                                    <td>...</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Этот отчёт был автоматически сгенерирован {{ $generated_at }}</p>
            <p>Для получения технической поддержки обратитесь в службу аналитики</p>
            <p style="margin-top: 10px; color: #9CA3AF;">
                Correlation ID: <span class="correlation-id">{{ $correlation_id }}</span>
            </p>
        </div>
    </div>
</body>
</html>
