<template>
  <div class="relative">
    <!-- Search Input -->
    <div class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 shadow-sm dark:bg-gray-800">
      <svg
        class="h-5 w-5 text-gray-400"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
        />
      </svg>

      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search documents..."
        class="flex-1 bg-transparent outline-none dark:text-white"
        @input="onSearch"
        @keydown.escape="clear"
      />

      <!-- Clear Button -->
      <button
        v-if="searchQuery"
        @click="clear"
        class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
      >
        ✕
      </button>
    </div>

    <!-- Search Results -->
    <div v-if="searchQuery && results.length > 0" class="absolute top-full left-0 right-0 z-50 mt-2 max-h-96 overflow-y-auto rounded-lg bg-white shadow-lg dark:bg-gray-800">
      <div
        v-for="(result, index) in results"
        :key="index"
        class="border-b border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
      >
        <p class="text-sm font-medium text-gray-900 dark:text-white">
          {{ result.name || result.title }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ result.type }} • {{ formatDate(result.created_at) }}
        </p>
      </div>
    </div>

    <!-- Search History -->
    <div
      v-else-if="!searchQuery && history.length > 0"
      class="absolute top-full left-0 right-0 z-50 mt-2 rounded-lg bg-white shadow-lg dark:bg-gray-800"
    >
      <div class="border-b border-gray-200 p-3 dark:border-gray-700">
        <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">Recent Searches</p>
      </div>

      <div
        v-for="(item, index) in history.slice(0, 5)"
        :key="index"
        class="border-b border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
      >
        <button @click="searchQuery = item.query; onSearch()" class="w-full text-left">
          <p class="text-sm text-gray-700 dark:text-gray-300">{{ item.query }}</p>
          <p class="text-xs text-gray-400">{{ item.results_count }} results</p>
        </button>
      </div>

      <button
        v-if="history.length > 0"
        @click="clearHistory"
        class="w-full border-t border-gray-200 p-2 text-center text-xs text-gray-500 hover:text-gray-700 dark:border-gray-700 dark:hover:text-gray-300"
      >
        Clear History
      </button>
    </div>

    <!-- Loading State -->
    <div
      v-if="isLoading"
      class="absolute top-full left-0 right-0 z-50 mt-2 rounded-lg bg-white p-4 text-center dark:bg-gray-800"
    >
      <p class="text-sm text-gray-500 dark:text-gray-400">Searching...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface SearchResult {
  id: number
  name?: string
  title?: string
  type: string
  created_at: string
}

interface HistoryItem {
  query: string
  results_count: number
  timestamp: string
}

const searchQuery = ref('')
const results = ref<SearchResult[]>([])
const history = ref<HistoryItem[]>([])
const isLoading = ref(false)
let searchTimeout: ReturnType<typeof setTimeout> | null = null

const onSearch = async () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  if (searchQuery.value.length < 2) {
    results.value = []
    return
  }

  searchTimeout = setTimeout(async () => {
    isLoading.value = true

    try {
      const response = await fetch(`/api/v2/search/documents?q=${encodeURIComponent(searchQuery.value)}&limit=10`)

      if (response.ok) {
        const data = await response.json()
        results.value = data.results || []
      }
    } catch (error) {
      console.error('Search failed:', error)
    } finally {
      isLoading.value = false
    }
  }, 300) // Debounce 300ms
}

const clear = () => {
  searchQuery.value = ''
  results.value = []
}

const clearHistory = async () => {
  try {
    await fetch('/api/v2/search/history/clear', { method: 'POST' })
    history.value = []
  } catch (error) {
    console.error('Failed to clear history:', error)
  }
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
}
</script>
