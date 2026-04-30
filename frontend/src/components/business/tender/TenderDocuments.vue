<template>
  <div class="tender-documents">
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="documents.length === 0" class="text-center py-8 text-gray-600">
      Документы пока не созданы
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="doc in documents"
        :key="doc.id"
        class="border rounded-lg p-4"
      >
        <div class="flex justify-between items-start mb-2">
          <div>
            <p class="font-semibold">{{ getDocTypeLabel(doc.type) }}</p>
            <p class="text-sm text-gray-500">{{ doc.number }}</p>
          </div>
          <span
            :class="[
              'px-2 py-1 text-xs font-medium rounded',
              getDocStatusClass(doc.status)
            ]"
          >
            {{ getDocStatusLabel(doc.status) }}
          </span>
        </div>
        
        <div class="text-sm text-gray-600 space-y-1">
          <p>Дата: {{ formatDate(doc.date) }}</p>
          <p v-if="doc.amount">Сумма: {{ formatAmount(doc.amount) }}</p>
          <p v-if="doc.commission_amount">
            Комиссия: {{ formatAmount(doc.commission_amount) }} ({{ doc.commission_percent }}%)
          </p>
        </div>

        <div v-if="doc.status === 'generated'" class="mt-4">
          <button
            @click="signDocument(doc.id)"
            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
          >
            Подписать
          </button>
        </div>

        <div v-if="doc.file_path" class="mt-4">
          <a
            :href="doc.file_path"
            target="_blank"
            class="text-blue-600 hover:underline text-sm"
          >
            Скачать документ
          </a>
        </div>
      </div>
    </div>

    <!-- Generate button -->
    <div v-if="canGenerate && documents.length === 0" class="mt-4">
      <button
        @click="generateDocuments"
        :disabled="generating"
        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50"
      >
        {{ generating ? 'Генерация...' : 'Сгенерировать документы' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import tenderApi from '@/services/tenderApi'

const props = defineProps<{
  tenderId: number
  bidId?: number
}>()

const documents = ref<any[]>([])
const loading = ref(false)
const generating = ref(false)

const canGenerate = computed(() => !!props.bidId)

const loadDocuments = async () => {
  loading.value = true
  try {
    // TODO: Implement getTenderDocuments in API
    // const data = await tenderApi.getTenderDocuments(props.tenderId)
    // documents.value = data
  } catch (error) {
    console.error('Error loading documents:', error)
  } finally {
    loading.value = false
  }
}

const generateDocuments = async () => {
  if (!props.bidId) return
  
  generating.value = true
  try {
    const data = await tenderApi.generateTenderDocuments(props.tenderId, props.bidId)
    documents.value = Object.values(data)
  } catch (error) {
    console.error('Error generating documents:', error)
    alert('Ошибка при генерации документов')
  } finally {
    generating.value = false
  }
}

const signDocument = async (documentId: number) => {
  if (!confirm('Подписать документ?')) return
  
  try {
    await tenderApi.signDocument('tender', documentId)
    await loadDocuments()
  } catch (error) {
    console.error('Error signing document:', error)
    alert('Ошибка при подписании документа')
  }
}

const getDocTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    contract: 'Договор',
    upd: 'УПД',
    invoice: 'Счет-фактура',
    act: 'Акт'
  }
  return labels[type] || type
}

const getDocStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    draft: 'Черновик',
    generated: 'Сгенерирован',
    signed: 'Подписан',
    cancelled: 'Отменен'
  }
  return labels[status] || status
}

const getDocStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    generated: 'bg-blue-100 text-blue-800',
    signed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatAmount = (amount: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(amount)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU')
}

onMounted(() => {
  loadDocuments()
})
</script>
