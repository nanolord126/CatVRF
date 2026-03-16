import { defineComponent } from 'vue';

export default defineComponent({
  name: 'CategoryFilter',
  props: {
    categories: {
      type: Array,
      default: () => [],
    },
  },
  emits: ['filter-change'],
  data() {
    return {
      selectedCategories: [],
    };
  },
  methods: {
    toggleCategory(categoryId) {
      const index = this.selectedCategories.indexOf(categoryId);
      if (index > -1) {
        this.selectedCategories.splice(index, 1);
      } else {
        this.selectedCategories.push(categoryId);
      }
      this.$emit('filter-change', this.selectedCategories);
    },
  },
  template: `
    <div class="bg-white p-4 rounded-lg shadow-md">
      <h3 class="text-lg font-semibold mb-4">Категории</h3>
      <div class="space-y-2">
        <label
          v-for="category in categories"
          :key="category.id"
          class="flex items-center cursor-pointer"
        >
          <input
            type="checkbox"
            :checked="selectedCategories.includes(category.id)"
            @change="toggleCategory(category.id)"
            class="w-4 h-4 text-blue-600"
          />
          <span class="ml-2 text-gray-700">{{ category.name }}</span>
        </label>
      </div>
    </div>
  `,
});
