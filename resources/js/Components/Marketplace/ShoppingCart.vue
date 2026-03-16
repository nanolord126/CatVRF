import { defineComponent } from 'vue';

export default defineComponent({
  name: 'ShoppingCart',
  props: {
    items: {
      type: Array,
      default: () => [],
    },
  },
  emits: ['remove-item', 'checkout'],
  computed: {
    total() {
      return this.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
    },
  },
  template: `
    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 class="text-2xl font-semibold mb-4">Корзина</h2>
      <div v-if="items.length === 0" class="text-gray-600 text-center py-8">
        Корзина пуста
      </div>
      <div v-else>
        <div class="space-y-4">
          <div
            v-for="item in items"
            :key="item.id"
            class="flex justify-between items-center border-b pb-4"
          >
            <div>
              <h4 class="font-semibold">{{ item.name }}</h4>
              <p class="text-gray-600 text-sm">{{ item.quantity }} x ₽{{ item.price }}</p>
            </div>
            <div class="flex items-center gap-4">
              <span class="font-semibold">₽{{ item.price * item.quantity }}</span>
              <button
                @click="$emit('remove-item', item.id)"
                class="text-red-600 hover:text-red-800"
              >
                Удалить
              </button>
            </div>
          </div>
        </div>
        <div class="mt-6 pt-4 border-t-2">
          <div class="flex justify-between items-center mb-4">
            <span class="text-lg font-semibold">Итого:</span>
            <span class="text-2xl font-bold text-blue-600">₽{{ total.toFixed(2) }}</span>
          </div>
          <button
            @click="$emit('checkout')"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-semibold"
          >
            Оформить заказ
          </button>
        </div>
      </div>
    </div>
  `,
});
