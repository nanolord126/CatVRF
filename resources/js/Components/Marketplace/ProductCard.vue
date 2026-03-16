import { defineComponent } from 'vue';

export default defineComponent({
  name: 'ProductCard',
  props: {
    product: {
      type: Object,
      required: true,
    },
  },
  emits: ['add-to-cart'],
  template: `
    <div class="bg-white rounded-lg shadow-md p-4">
      <div v-if="product.image" class="mb-4">
        <img :src="product.image" :alt="product.name" class="w-full h-48 object-cover rounded" />
      </div>
      <h3 class="text-lg font-semibold text-gray-900">{{ product.name }}</h3>
      <p class="text-gray-600 text-sm mt-2">{{ product.description }}</p>
      <div class="flex justify-between items-center mt-4">
        <span class="text-xl font-bold text-blue-600">₽{{ product.price }}</span>
        <button
          @click="$emit('add-to-cart', product)"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
          Добавить
        </button>
      </div>
    </div>
  `,
});
