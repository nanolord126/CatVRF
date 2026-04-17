<template>
  <div class="medical-search-card">
    <SearchCard
      :id="id"
      vertical="medical"
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
  // Medical-specific criteria (vertical_restricted - только для Medical)
  specialty?: string;
  acceptsInsurance?: boolean;
  isEmergency?: boolean;
  hasOnlineConsultation?: boolean;
  experienceYears?: number;
}

const props = withDefaults(defineProps<Props>(), {
  imageUrl: '',
  rating: 0,
  pricePeriod: '',
  badge: '',
  isFeatured: false,
  actionLabel: 'Записаться',
  specialty: '',
  acceptsInsurance: false,
  isEmergency: false,
  hasOnlineConsultation: false,
  experienceYears: 0,
});

const emit = defineEmits<{
  (e: 'action', id: string | number): void;
  (e: 'favorite', id: string | number): void;
}>();

const specialtyNames: Record<string, string> = {
  general_practitioner: 'Терапевт',
  cardiologist: 'Кардиолог',
  dermatologist: 'Дерматолог',
  pediatrician: 'Педиатр',
  surgeon: 'Хирург',
  neurologist: 'Невролог',
  dentist: 'Стоматолог',
  ophthalmologist: 'Офтальмолог',
  orthopedist: 'Ортопед',
  gynecologist: 'Гинеколог',
};

const allCriteria = computed(() => {
  const criteria = [];

  // Публичные критерии (индексируются для всех вертикалей)
  // (унаследованы из SearchCard)

  // Вертикально-специфичные критерии (только для Medical)
  // Эти критерии НЕ появятся в Beauty, Auto, Hotels и т.д.
  if (props.specialty) {
    criteria.push({
      code: 'specialty',
      label: 'Специальность',
      value: specialtyNames[props.specialty] || props.specialty,
      type: 'vertical_restricted' as const,
    });
  }

  if (props.acceptsInsurance) {
    criteria.push({
      code: 'accepts_insurance',
      label: 'Страховка',
      value: 'Принимает',
      type: 'vertical_restricted' as const,
    });
  }

  if (props.isEmergency) {
    criteria.push({
      code: 'emergency',
      label: 'Экстренный',
      value: '24/7',
      type: 'vertical_restricted' as const,
    });
  }

  if (props.hasOnlineConsultation) {
    criteria.push({
      code: 'online_consultation',
      label: 'Онлайн',
      value: 'Доступно',
      type: 'vertical_restricted' as const,
    });
  }

  if (props.experienceYears > 0) {
    criteria.push({
      code: 'experience',
      label: 'Опыт',
      value: `${props.experienceYears} лет`,
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
.medical-search-card {
  width: 100%;
}
</style>
