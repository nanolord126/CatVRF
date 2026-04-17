<script setup lang="ts">
import { ref } from 'vue';

interface Document {
  id: number;
  name: string;
  type: string;
  status: 'pending' | 'uploaded' | 'verified' | 'rejected';
  file_url?: string;
  rejection_reason?: string;
}

const documents = ref<Document[]>([
  { id: 1, name: 'Договор купли-продажи', type: 'contract', status: 'pending' },
  { id: 2, name: 'Технический паспорт', type: 'tech_pass', status: 'pending' },
  { id: 3, name: 'VIN-код (фото)', type: 'vin_photo', status: 'pending' },
  { id: 4, name: 'Сертификат соответствия', type: 'certificate', status: 'pending' },
  { id: 5, name: 'Транспортная накладная', type: 'waybill', status: 'pending' },
]);

const uploadingDoc = ref<number | null>(null);

const handleFileUpload = async (docId: number, event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  
  if (!file) return;

  uploadingDoc.value = docId;

  try {
    const formData = new FormData();
    formData.append('document', file);
    formData.append('document_type', documents.value.find(d => d.id === docId)?.type || '');

    const response = await fetch('/api/v1/auto/import/documents/upload', {
      method: 'POST',
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      const docIndex = documents.value.findIndex(d => d.id === docId);
      if (docIndex !== -1) {
        documents.value[docIndex] = {
          ...documents.value[docIndex],
          status: 'uploaded',
          file_url: data.file_url,
        };
      }
    }
  } catch (error) {
    console.error('Error uploading document:', error);
  } finally {
    uploadingDoc.value = null;
  }
};

const getStatusIcon = (status: string): string => {
  switch (status) {
    case 'pending': return '⏳';
    case 'uploaded': return '📤';
    case 'verified': return '✅';
    case 'rejected': return '❌';
    default: return '⏳';
  }
};

const getStatusClass = (status: string): string => {
  switch (status) {
    case 'pending': return 'bg-gray-100 text-gray-600';
    case 'uploaded': return 'bg-blue-100 text-blue-800';
    case 'verified': return 'bg-green-100 text-green-800';
    case 'rejected': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-600';
  }
};
</script>

<template>
  <div class="car-import-documents max-w-4xl mx-auto p-6 bg-white rounded-xl shadow-lg">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Документы для импорта</h2>

    <div class="space-y-4">
      <div 
        v-for="doc in documents" 
        :key="doc.id"
        class="p-4 border rounded-lg"
        :class="{
          'border-gray-200': doc.status === 'pending',
          'border-blue-300 bg-blue-50': doc.status === 'uploaded',
          'border-green-300 bg-green-50': doc.status === 'verified',
          'border-red-300 bg-red-50': doc.status === 'rejected'
        }"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-2xl">{{ getStatusIcon(doc.status) }}</span>
            <div>
              <p class="font-medium text-gray-900">{{ doc.name }}</p>
              <p 
                v-if="doc.rejection_reason" 
                class="text-sm text-red-600 mt-1"
              >
                {{ doc.rejection_reason }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <span 
              class="px-3 py-1 rounded-full text-sm font-medium"
              :class="getStatusClass(doc.status)"
            >
              {{ doc.status === 'pending' ? 'Ожидает' : 
                 doc.status === 'uploaded' ? 'Загружен' :
                 doc.status === 'verified' ? 'Проверен' : 'Отклонен' }}
            </span>

            <input
              type="file"
              :id="`file-${doc.id}`"
              class="hidden"
              accept=".pdf,.jpg,.jpeg,.png"
              @change="handleFileUpload(doc.id, $event)"
              :disabled="uploadingDoc === doc.id || doc.status === 'verified'"
            />

            <button
              @click="$refs[`file-${doc.id}`]?.click()"
              :disabled="uploadingDoc === doc.id || doc.status === 'verified'"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
            >
              {{ uploadingDoc === doc.id ? 'Загрузка...' : 'Загрузить' }}
            </button>

            <a 
              v-if="doc.file_url"
              :href="doc.file_url"
              target="_blank"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
            >
              Просмотр
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-6 p-4 bg-yellow-50 text-yellow-800 rounded-lg">
      <p class="font-medium">ℹ️ Требования к документам:</p>
      <ul class="list-disc list-inside mt-2 text-sm space-y-1">
        <li>Формат: PDF, JPG, PNG</li>
        <li>Максимальный размер: 10 МБ</li>
        <li>Разрешение фото: минимум 1200x800 пикселей</li>
        <li>Все документы должны быть переведены на русский язык</li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.car-import-documents {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>
