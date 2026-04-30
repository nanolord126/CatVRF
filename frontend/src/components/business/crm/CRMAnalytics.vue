<template>
  <div class="crm-analytics">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Воронка лидов</div>
        <div class="mt-2">
          <div class="flex justify-between text-xs mb-1">
            <span>Новые</span>
            <span class="font-medium">{{ funnel.new }}</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full" :style="{ width: funnelPercentage.new + '%' }"></div>
          </div>
        </div>
        <div class="mt-2">
          <div class="flex justify-between text-xs mb-1">
            <span>Квалифицированы</span>
            <span class="font-medium">{{ funnel.qualified }}</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-green-600 h-2 rounded-full" :style="{ width: funnelPercentage.qualified + '%' }"></div>
          </div>
        </div>
        <div class="mt-2">
          <div class="flex justify-between text-xs mb-1">
            <span>Конвертированы</span>
            <span class="font-medium">{{ funnel.converted }}</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-purple-600 h-2 rounded-full" :style="{ width: funnelPercentage.converted + '%' }"></div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Конверсия по каналам</div>
        <div class="mt-4 space-y-2">
          <div
            v-for="channel in conversionChannels"
            :key="channel.name"
            class="flex justify-between items-center"
          >
            <span class="text-sm text-gray-700">{{ channel.name }}</span>
            <span class="text-sm font-medium" :class="getConversionClass(channel.rate)">
              {{ channel.rate }}%
            </span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Активность за месяц</div>
        <div class="mt-4 space-y-2">
          <div class="flex justify-between">
            <span class="text-sm text-gray-700">Создано лидов</span>
            <span class="text-sm font-medium text-gray-900">{{ activity.leads_created }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-700">Контактировано</span>
            <span class="text-sm font-medium text-gray-900">{{ activity.contacts_made }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-700">Сделки</span>
            <span class="text-sm font-medium text-gray-900">{{ activity.deals_closed }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-700">Выручка</span>
            <span class="text-sm font-medium text-green-600">{{ formatCurrency(activity.revenue) }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Топ сегменты</div>
        <div class="mt-4 space-y-2">
          <div
            v-for="(segment, index) in topSegments"
            :key="segment.name"
            class="flex items-center gap-2"
          >
            <span class="text-lg font-bold text-gray-400">{{ index + 1 }}</span>
            <div class="flex-1">
              <div class="flex justify-between text-sm">
                <span class="text-gray-700">{{ segment.name }}</span>
                <span class="font-medium text-gray-900">{{ segment.count }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-1 mt-1">
                <div class="bg-cyan-600 h-1 rounded-full" :style="{ width: segment.percentage + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Лиды по месяцам</h3>
        <div class="h-64 flex items-end justify-between gap-2">
          <div
            v-for="month in monthlyLeads"
            :key="month.month"
            class="flex-1 flex flex-col items-center"
          >
            <div
              class="w-full bg-cyan-500 rounded-t transition-all hover:bg-cyan-600"
              :style="{ height: (month.count / maxLeads * 100) + '%' }"
            ></div>
            <span class="text-xs text-gray-600 mt-2">{{ month.month }}</span>
            <span class="text-xs font-medium text-gray-900">{{ month.count }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Распределение по статусам</h3>
        <div class="h-64 flex items-center justify-center">
          <div class="relative w-48 h-48">
            <svg viewBox="0 0 36 36" class="w-full h-full">
              <path
                v-for="(slice, index) in statusDistribution"
                :key="slice.status"
                :d="getSlicePath(slice, index)"
                :fill="slice.color"
                stroke="white"
                stroke-width="0.5"
              />
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">{{ totalLeads }}</div>
                <div class="text-xs text-gray-600">Всего</div>
              </div>
            </div>
          </div>
          <div class="ml-8 space-y-2">
            <div
              v-for="slice in statusDistribution"
              :key="slice.status"
              class="flex items-center gap-2"
            >
              <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: slice.color }"></div>
              <span class="text-sm text-gray-700">{{ slice.label }}</span>
              <span class="text-sm font-medium text-gray-900">{{ slice.count }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const funnel = ref({
  new: 150,
  qualified: 80,
  converted: 45
})

const funnelPercentage = computed(() => {
  const total = funnel.value.new
  return {
    new: 100,
    qualified: (funnel.value.qualified / total * 100).toFixed(1),
    converted: (funnel.value.converted / total * 100).toFixed(1)
  }
})

const conversionChannels = ref([
  { name: 'Веб-сайт', rate: 12.5 },
  { name: 'Рефералы', rate: 18.3 },
  { name: 'Соцсети', rate: 8.7 },
  { name: 'Выставки', rate: 22.1 }
])

const activity = ref({
  leads_created: 234,
  contacts_made: 456,
  deals_closed: 67,
  revenue: 1250000
})

const topSegments = ref([
  { name: 'Retail', count: 45, percentage: 35 },
  { name: 'B2B', count: 38, percentage: 30 },
  { name: 'HoReCa', count: 28, percentage: 22 },
  { name: 'E-commerce', count: 17, percentage: 13 }
])

const monthlyLeads = ref([
  { month: 'Янв', count: 45 },
  { month: 'Фев', count: 52 },
  { month: 'Мар', count: 68 },
  { month: 'Апр', count: 75 },
  { month: 'Май', count: 82 },
  { month: 'Июн', count: 95 }
])

const statusDistribution = ref([
  { status: 'new', label: 'Новые', count: 150, color: '#3B82F6' },
  { status: 'contacted', label: 'Контактированы', count: 80, color: '#F59E0B' },
  { status: 'qualified', label: 'Квалифицированы', count: 60, color: '#10B981' },
  { status: 'converted', label: 'Конвертированы', count: 45, color: '#8B5CF6' },
  { status: 'lost', label: 'Потеряны', count: 30, color: '#EF4444' }
])

const maxLeads = computed(() => Math.max(...monthlyLeads.value.map(m => m.count)))

const totalLeads = computed(() => statusDistribution.value.reduce((sum, s) => sum + s.count, 0))

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(amount)
}

const getConversionClass = (rate: number) => {
  if (rate >= 15) return 'text-green-600'
  if (rate >= 10) return 'text-yellow-600'
  return 'text-red-600'
}

const getSlicePath = (slice: any, index: number) => {
  // Simplified pie chart path calculation
  const total = statusDistribution.value.reduce((sum, s) => sum + s.count, 0)
  const startAngle = index === 0 ? 0 : statusDistribution.value.slice(0, index).reduce((sum, s) => sum + s.count, 0) / total * 360
  const endAngle = (statusDistribution.value.slice(0, index + 1).reduce((sum, s) => sum + s.count, 0) / total * 360)
  
  const startRad = (startAngle - 90) * Math.PI / 180
  const endRad = (endAngle - 90) * Math.PI / 180
  
  const x1 = 18 + 15.915 * Math.cos(startRad)
  const y1 = 18 + 15.915 * Math.sin(startRad)
  const x2 = 18 + 15.915 * Math.cos(endRad)
  const y2 = 18 + 15.915 * Math.sin(endRad)
  
  const largeArcFlag = endAngle - startAngle > 180 ? 1 : 0
  
  return `M 18 18 L ${x1} ${y1} A 15.915 15.915 0 ${largeArcFlag} 1 ${x2} ${y2} Z`
}
</script>
