<template>
  <div class="food-search-card">
    <SearchCard
      :id="id"
      vertical="food"
      :title="title"
      :description="description"
      :image-url="imageUrl"
      :rating="rating"
      :price="price"
      :price-period="pricePeriod"
      :badge="badge"
      :is-featured="isFeatured"
      :action-label="actionLabel"
      :criteria="allCriteria"
      @action="handleAction"
      @favorite="handleFavorite"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import SearchCard from './SearchCard.vue';

interface Props {
  id: string | number;
  title: string;
  description: string;
  imageUrl?: string;
  rating?: number;
  price: number;
  pricePeriod?: string;
  badge?: string;
  isFeatured?: boolean;
  actionLabel?: string;
  cuisineType?: string;
  deliveryTime?: number;
  isDeliveryAvailable?: boolean;
  isVegetarian?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  imageUrl: '',
  rating: 0,
  pricePeriod: '',
  badge: '',
  isFeatured: false,
  actionLabel: 'Заказать',
  cuisineType: '',
  deliveryTime: 0,
  isDeliveryAvailable: false,
  isVegetarian: false,
});

const emit = defineEmits<{
  (e: 'action', id: string | number): void;
  (e: 'favorite', id: string | number): void;
}>();

const cuisineNames: Record<string, string> = {
  italian: 'Итальянская',
  japanese: 'Японская',
  chinese: 'Китайская',
  russian: 'Русская',
  french: 'Французская',
  mexican: 'Мексиканская',
  indian: 'Индийская',
  thai: 'Тайская',
};

const allCriteria = computed(() => {
  const criteria = [];

  if (props.cuisineType) {
    criteria.push({
      code: 'cuisine_type',
      label: 'Кухня',
      value: cuisineNames[props.cuisineType] || props.cuisineType,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.deliveryTime > 0) {
    criteria.push({
      code: 'delivery_time',
      label: 'Доставка',
      value: `${props.deliveryTime} мин`,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.isDeliveryAvailable) {
    criteria.push({
      code: 'delivery_available',
      label: 'Доставка',
      value: 'Да',
      type: 'vertical_restricted' as const,
    });
  }

  if (props.isVegetarian) {
    criteria.push({
      code: 'vegetarian',
      label: 'Вегетарианское',
      value: 'Да',
      type: 'vertical_restricted' as const,
    });
  }

  return criteria;
});

const handleAction = (id: string | number) => {
  emit('action', id);
};

const handleFavorite = (id: string | number) => {
  emit('favorite', id);
};
</script>

<style scoped>
.food-search-card {
  width: 100%;
}
</style>
