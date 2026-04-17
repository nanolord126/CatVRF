<template>
  <div class="fashion-search-card">
    <SearchCard
      :id="id"
      vertical="fashion"
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
  clothingType?: string;
  brand?: string;
  size?: string;
  isNew?: boolean;
  onSale?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  imageUrl: '',
  rating: 0,
  pricePeriod: '',
  badge: '',
  isFeatured: false,
  actionLabel: 'Купить',
  clothingType: '',
  brand: '',
  size: '',
  isNew: false,
  onSale: false,
});

const emit = defineEmits<{
  (e: 'action', id: string | number): void;
  (e: 'favorite', id: string | number): void;
}>();

const clothingTypeNames: Record<string, string> = {
  dress: 'Платье',
  shirt: 'Рубашка',
  pants: 'Брюки',
  jacket: 'Куртка',
  shoes: 'Обувь',
  accessories: 'Аксессуары',
};

const allCriteria = computed(() => {
  const criteria = [];

  if (props.clothingType) {
    criteria.push({
      code: 'clothing_type',
      label: 'Тип',
      value: clothingTypeNames[props.clothingType] || props.clothingType,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.brand) {
    criteria.push({
      code: 'brand',
      label: 'Бренд',
      value: props.brand,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.size) {
    criteria.push({
      code: 'size',
      label: 'Размер',
      value: props.size,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.isNew) {
    criteria.push({
      code: 'is_new',
      label: 'Новинка',
      value: 'Да',
      type: 'vertical_restricted' as const,
    });
  }

  if (props.onSale) {
    criteria.push({
      code: 'on_sale',
      label: 'Скидка',
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
.fashion-search-card {
  width: 100%;
}
</style>
