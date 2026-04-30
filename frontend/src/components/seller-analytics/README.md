# Seller Analytics Dashboard

Comprehensive seller analytics dashboard with real-time metrics, AI-powered insights, and data visualization for the CatVRF marketplace platform.

## Features

- **KPI Cards**: Display key performance indicators (GMV, Orders, AOV, Conversion Rate, Active Products, Revenue to Payout) with trend indicators
- **Trend Charts**: Interactive charts using Chart.js for visualizing trends over time (line and bar charts)
- **AI Insights**: Automated insights with severity levels (critical, warning, opportunity, info) and actionable recommendations
- **Top Products Table**: Performance ranking with revenue, orders, and conversion rate metrics
- **Excel Export**: Download complete analytics data with multiple sheets
- **Period Selection**: Flexible time periods (today, 7/30/90 days)
- **Responsive Design**: Mobile-friendly layout with TailwindCSS

## Architecture

### Frontend Components

```
frontend/src/components/seller-analytics/
├── KPICard.vue                    # KPI metric display component
├── TrendChart.vue                # Chart.js integration for trends
├── InsightsWidget.vue            # AI insights display
├── TopProductsTable.vue          # Product performance table
├── SellerAnalyticsDashboard.vue  # Main dashboard component
├── index.ts                      # Component exports
└── __tests__/                    # Unit tests
    ├── KPICard.spec.ts
    ├── TrendChart.spec.ts
    ├── InsightsWidget.spec.ts
    ├── TopProductsTable.spec.ts
    └── SellerAnalyticsDashboard.spec.ts
```

### Backend Services

```
modules/Analytics/
├── Application/
│   ├── Services/
│   │   └── SellerAnalyticsService.php    # Business logic layer
│   └── DTOs/
│       ├── SellerDashboardDTO.php
│       ├── KPICardDTO.php
│       ├── SellerInsightDTO.php
│       ├── TimeSeriesDto.php
│       └── TopItemsDto.php
└── Infrastructure/
    └── Http/
        └── Controllers/
            ├── AnalyticsApiController.php    # REST API endpoints
            └── SellerAnalyticsExportController.php
```

## Installation

### Dependencies

The following packages are required:

```bash
npm install chart.js vue-chartjs xlsx
```

### TypeScript Types

Type definitions are located in `frontend/src/types/analytics.ts`:

- `KPICard` - KPI card data structure
- `Trend` - Trend data with data points
- `TopProduct` - Product performance data
- `SellerInsight` - AI insight structure
- `SellerDashboardResponse` - Complete dashboard response
- `ProductAnalyticsResponse` - Product analytics with pagination
- `PeriodType` - Time period type

## Usage

### Basic Integration

```vue
<script setup lang="ts">
import { SellerAnalyticsDashboard } from '@/components/seller-analytics'
</script>

<template>
  <SellerAnalyticsDashboard />
</template>
```

### Using Individual Components

```vue
<script setup lang="ts">
import { KPICard, TrendChart, InsightsWidget, TopProductsTable } from '@/components/seller-analytics'
import type { KPICard as KPICardType } from '@/types/analytics'

const kpiData: KPICardType = {
  label: 'GMV',
  value: 12500.50,
  previous_value: 10000,
  growth_rate: 25,
  format: 'currency',
  trend: 'up',
  icon: '💰',
}
</script>

<template>
  <div class="grid grid-cols-4 gap-4">
    <KPICard :kpi="kpiData" />
  </div>
</template>
```

### Using the Composable

```vue
<script setup lang="ts">
import { onMounted } from 'vue'
import { useSellerAnalytics } from '@/composables/useSellerAnalytics'
import type { PeriodType } from '@/types/analytics'

const {
  loading,
  error,
  dashboardData,
  kpiCards,
  trends,
  topProducts,
  insights,
  fetchDashboard,
  fetchProductAnalytics,
  fetchInsights,
  exportProductAnalytics,
} = useSellerAnalytics()

const selectedPeriod: PeriodType = 'last_30_days'

onMounted(() => {
  fetchDashboard(selectedPeriod)
  fetchInsights(selectedPeriod)
})
</script>
```

## API Endpoints

### Dashboard Data

**GET** `/api/analytics/seller/dashboard?period=last_30_days`

Returns complete dashboard data including KPI cards, trends, top products, and insights.

### Product Analytics

**GET** `/api/analytics/seller/products?period=last_30_days&page=1&per_page=50`

Returns paginated product analytics table data.

### AI Insights

**GET** `/api/analytics/seller/insights?period=last_30_days`

Returns AI-powered insights with recommendations.

### Excel Export

**GET** `/api/analytics/seller/export/products?period=last_30_days`

Downloads Excel file with product metrics.

## Data Flow

```
User Request (Vue Component)
    ↓
useSellerAnalytics Composable
    ↓
API Call (fetch)
    ↓
Laravel Route (analytics.api.php)
    ↓
AnalyticsApiController
    ↓
SellerAnalyticsService (Business Logic)
    ↓
Aggregated Queries (SellerDailyMetrics, ProductMetrics)
    ↓
DTOs (Data Transfer Objects)
    ↓
JSON Response
    ↓
Vue Component (Display)
```

## Caching Strategy

The backend uses Redis caching with cache tags for performance:

```php
// Cache key: "seller:dashboard:{tenantId}:{sellerId}:{period}"
// Cache tags: ["seller_analytics:{tenantId}", "seller:{sellerId}"]
// TTL: 300 seconds (5 minutes) for dashboard
// TTL: 600 seconds (10 minutes) for top products
```

Cache is invalidated automatically when new data is aggregated via `invalidateSellerCache()`.

## Performance

- **Target**: < 800ms dashboard load time
- **Achieved**: Through aggregate tables, Redis caching, indexed queries
- **Scale**: Handles 5k-50k+ RPS with proper caching

## KPI Metrics

| Metric | Description | Format |
|--------|-------------|--------|
| GMV | Gross Merchandise Value | Currency |
| Orders | Total number of orders | Number |
| AOV | Average Order Value | Currency |
| Conversion Rate | Orders / Views | Percentage |
| Active Products | Products with sales | Number |
| Revenue to Payout | Revenue minus commission | Currency |

## Trend Charts

- **GMV Trend**: Revenue over time
- **Orders Trend**: Order volume over time
- **Chart Types**: Line (default), Bar
- **Time Granularity**: Daily data points
- **Colors**: Automatic color assignment for multiple trends

## AI Insights

Insights are generated by analyzing trends and detecting anomalies:

### Insight Types

1. **Revenue Drop**: Detects significant revenue decreases (>15%)
2. **Rating Issues**: Alerts for low average ratings (<4.2)
3. **Price Optimization**: Suggests pricing adjustments
4. **Inventory Alerts**: Low stock or overstock warnings
5. **Seasonal Trends**: Pattern recognition for seasonal products

### Severity Levels

- **Critical**: Immediate action required (red)
- **Warning**: Attention needed (yellow)
- **Opportunity**: Growth potential (green)
- **Info**: General information (gray)

## Excel Export

Export includes multiple sheets:

1. **KPI Cards**: All KPI metrics with previous values and growth rates
2. **Trends**: Time series data for all trends
3. **Top Products**: Product performance metrics
4. **Insights**: AI insights with recommendations

Filename format: `seller-analytics-{period}-{date}.xlsx`

## Testing

### Run Component Tests

```bash
npm run test frontend/src/components/seller-analytics/__tests__/
```

### Run Composable Tests

```bash
npm run test frontend/src/composables/__tests__/useSellerAnalytics.spec.ts
```

### Test Coverage

- KPICard: 7 test cases
- TrendChart: 7 test cases
- InsightsWidget: 12 test cases
- TopProductsTable: 12 test cases
- SellerAnalyticsDashboard: 6 test cases
- useSellerAnalytics: 20+ test cases

## Styling

Components use TailwindCSS with consistent design tokens:

- **Primary Color**: Indigo-600
- **Success Color**: Green-600
- **Warning Color**: Yellow-600
- **Error Color**: Red-600
- **Gray Scale**: Gray-50 to Gray-900
- **Border Radius**: rounded-xl (12px)
- **Shadow**: shadow-sm, shadow-md

## Accessibility

- Semantic HTML elements
- ARIA labels where appropriate
- Keyboard navigation support
- High contrast ratios for text
- Loading states for async operations

## Security

- Authentication required (auth:sanctum middleware)
- Tenant-aware access control
- Rate limiting on API endpoints
- PII anonymization for medical data (152-FZ compliance)
- Audit logging for all analytics access

## Future Enhancements

- [ ] Custom date range picker
- [ ] Category filters
- [ ] Period comparison (current vs previous)
- [ ] Real-time metrics (WebSocket updates)
- [ ] Drill-down capabilities
- [ ] Custom metric creation
- [ ] Report scheduling
- [ ] Email/SMS alerts for critical insights
- [ ] Multi-seller comparison
- [ ] Forecasting with ML models

## Troubleshooting

### Dashboard Not Loading

1. Check authentication status
2. Verify tenant ID in headers
3. Check API endpoint availability
4. Review browser console for errors

### Charts Not Rendering

1. Verify Chart.js is installed
2. Check canvas element exists
3. Ensure data format is correct
4. Review console for Chart.js errors

### Export Not Working

1. Verify xlsx library is installed
2. Check browser download permissions
3. Verify API endpoint responds correctly
4. Check file size limits

### Slow Performance

1. Clear cache: `php artisan cache:clear`
2. Check Redis connection
3. Verify database indexes exist
4. Review query performance with EXPLAIN

## Contributing

When adding new features:

1. Follow Clean Architecture principles
2. Add TypeScript types for all new data structures
3. Write unit tests for new components
4. Update API documentation
5. Test with different time periods
6. Verify mobile responsiveness
7. Check accessibility compliance

## License

Part of CatVRF Marketplace Platform - Internal Use Only

## Support

For issues or questions, contact the Analytics Team or create an issue in the project repository.
