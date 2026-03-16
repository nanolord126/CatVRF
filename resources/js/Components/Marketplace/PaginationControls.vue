import { defineComponent } from 'vue';

export default defineComponent({
  name: 'PaginationControls',
  props: {
    currentPage: {
      type: Number,
      default: 1,
    },
    totalPages: {
      type: Number,
      default: 1,
    },
  },
  emits: ['page-change'],
  methods: {
    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.$emit('page-change', page);
      }
    },
  },
  template: `
    <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 mt-8">
      <button
        @click="goToPage(currentPage - 1)"
        :disabled="currentPage === 1"
        class="px-3 py-1 rounded border disabled:opacity-50"
      >
        ← Назад
      </button>
      <span class="px-4 py-1">
        Страница {{ currentPage }} из {{ totalPages }}
      </span>
      <button
        @click="goToPage(currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="px-3 py-1 rounded border disabled:opacity-50"
      >
        Вперёд →
      </button>
    </div>
  `,
});
