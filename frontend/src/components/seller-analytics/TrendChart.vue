<template>
  <div class="trend-chart bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <!-- Loading State -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-4 bg-gray-200 rounded w-1/3 mb-4"></div>
      <div class="h-64 bg-gray-200 rounded"></div>
    </div>

    <!-- Content -->
    <div v-else>
      <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ title }}</h3>
      <div :style="{ height: `${height}px` }">
        <canvas ref="chartCanvas"></canvas>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import {
  Chart,
  ChartConfiguration,
  LineController,
  BarController,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import type { Trend } from '@/types/analytics'

// Register Chart.js components
Chart.register(
  LineController,
  BarController,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

interface Props {
  trends: Trend[]
  height?: number
  loading?: boolean
  chartType?: 'line' | 'bar'
}

const props = withDefaults(defineProps<Props>(), {
  height: 300,
  loading: false,
  chartType: 'line'
})

const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const title = computed(() => {
  return props.trends.length > 0 ? props.trends[0].label : 'Trend Chart'
})

const chartData = computed(() => {
  if (props.trends.length === 0) return null

  const colors = [
    { borderColor: '#3B82F6', backgroundColor: 'rgba(59, 130, 246, 0.1)' },
    { borderColor: '#10B981', backgroundColor: 'rgba(16, 185, 129, 0.1)' },
    { borderColor: '#F59E0B', backgroundColor: 'rgba(245, 158, 11, 0.1)' },
    { borderColor: '#8B5CF6', backgroundColor: 'rgba(139, 92, 246, 0.1)' },
    { borderColor: '#EC4899', backgroundColor: 'rgba(236, 72, 153, 0.1)' },
  ]

  const labels = props.trends[0].dataPoints.map(dp => dp.date)

  const datasets = props.trends.map((trend, index) => {
    const color = colors[index % colors.length]
    return {
      label: trend.label,
      data: trend.dataPoints.map(dp => dp.value),
      borderColor: color.borderColor,
      backgroundColor: color.backgroundColor,
      fill: props.chartType === 'line',
      tension: 0.4,
      borderWidth: 2,
      pointRadius: 3,
      pointHoverRadius: 5,
    }
  })

  return {
    labels,
    datasets
  }
})

const createChart = () => {
  if (!chartCanvas.value || !chartData.value) return

  if (chartInstance) {
    chartInstance.destroy()
  }

  const config: ChartConfiguration = {
    type: props.chartType,
    data: chartData.value,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      plugins: {
        legend: {
          display: props.trends.length > 1,
          position: 'top',
          labels: {
            usePointStyle: true,
            padding: 20,
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          titleFont: {
            size: 14,
            weight: 'bold'
          },
          bodyFont: {
            size: 13
          },
          cornerRadius: 8,
        }
      },
      scales: {
        x: {
          grid: {
            display: false
          },
          ticks: {
            maxRotation: 45,
            minRotation: 45,
          }
        },
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            callback: function(value: any) {
              if (value >= 1000000) {
                return (value / 1000000).toFixed(1) + 'M'
              }
              if (value >= 1000) {
                return (value / 1000).toFixed(1) + 'K'
              }
              return value
            }
          }
        }
      }
    }
  }

  chartInstance = new Chart(chartCanvas.value, config)
}

onMounted(() => {
  createChart()
})

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})

watch(() => props.trends, () => {
  createChart()
}, { deep: true })

watch(() => props.chartType, () => {
  createChart()
})
</script>
