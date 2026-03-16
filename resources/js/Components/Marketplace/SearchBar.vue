import { defineComponent } from 'vue';

export default defineComponent({
  name: 'SearchBar',
  props: {
    placeholder: {
      type: String,
      default: 'Поиск...',
    },
  },
  emits: ['search'],
  data() {
    return {
      query: '',
    };
  },
  methods: {
    handleSearch() {
      this.$emit('search', this.query);
    },
  },
  template: `
    <div class="w-full">
      <input
        v-model="query"
        @input="handleSearch"
        type="text"
        :placeholder="placeholder"
        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-600"
      />
    </div>
  `,
});
