<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Analytics Report</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px; }
        .section { margin-bottom: 20px; }
        .section h2 { color: #4F46E5; margin-top: 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-value { font-size: 2em; font-weight: bold; color: #4F46E5; }
        .stat-label { color: #666; font-size: 0.9em; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f3f4f6; font-weight: 600; }
        tr:hover { background: #f9fafb; }
        .positive { color: #10B981; }
        .negative { color: #EF4444; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Customer Analytics Report</h1>
            <p>Supermarket CRM - Last {{ $days }} Days</p>
        </div>
        
        <div class="content">
            <div class="section">
                <h2>Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value">{{ number_format($analytics['total_customers']) }}</div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ number_format($analytics['new_customers']) }}</div>
                        <div class="stat-label">New Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ number_format($analytics['active_customers']) }}</div>
                        <div class="stat-label">Active Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ number_format($analytics['vip_customers']) }}</div>
                        <div class="stat-label">VIP Customers</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Revenue & Orders</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value">{{ number_format($analytics['total_revenue'], 0, ',', ' ') }} ₽</div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ number_format($analytics['subscription_users']) }}</div>
                        <div class="stat-label">Subscription Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ number_format($analytics['returns_count']) }}</div>
                        <div class="stat-label">Returns</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ number_format($analytics['sleeping_customers']) }}</div>
                        <div class="stat-label">Sleeping Customers</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Top 10 Customers by LTV</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Total Spent</th>
                            <th>Orders</th>
                            <th>Loyalty Tier</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topCustomers as $customer)
                        <tr>
                            <td>{{ $customer['name'] }}</td>
                            <td>{{ $customer['email'] ?? '-' }}</td>
                            <td>{{ number_format($customer['total_spent'], 0, ',', ' ') }} ₽</td>
                            <td>{{ $customer['orders_count'] }}</td>
                            <td>
                                <span style="padding: 4px 8px; border-radius: 4px; background: {{ match($customer['loyalty_tier']) {
                                    'Platinum' => '#9333EA',
                                    'Gold' => '#F59E0B',
                                    'Silver' => '#6B7280',
                                    default => '#9CA3AF',
                                }} }; color: white;">
                                    {{ $customer['loyalty_tier'] }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="section">
                <p style="color: #666; font-size: 0.9em;">
                    Report generated on {{ date('Y-m-d H:i:s') }}<br>
                    This is an automated report from CatVRF Supermarket CRM
                </p>
            </div>
        </div>
    </div>
</body>
</html>
