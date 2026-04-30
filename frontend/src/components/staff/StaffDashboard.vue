<template>
  <div class="staff-dashboard">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard
        title="Всего сотрудников"
        :value="stats.total"
        icon="users"
        color="blue"
      />
      <StatCard
        title="Активных"
        :value="stats.active"
        icon="user-check"
        color="green"
      />
      <StatCard
        title="На смене"
        :value="stats.onShift"
        icon="clock"
        color="purple"
      />
      <StatCard
        title="В отпуске"
        :value="stats.onVacation"
        icon="calendar"
        color="orange"
      />
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Staff List -->
      <div class="lg:col-span-2">
        <StaffList
          :staff="staff"
          :loading="loading"
          @refresh="loadStaff"
          @filter="setFilter"
        />
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Top Performers -->
        <TopPerformers :performers="topPerformers" />

        <!-- AI Insights -->
        <AIInsights :insights="aiInsights" />

        <!-- Gamification -->
        <Leaderboard :leaderboard="leaderboard" />

        <!-- Upcoming Shifts -->
        <UpcomingShifts :shifts="upcomingShifts" />
      </div>
    </div>

    <!-- Modals -->
    <StaffModal
      v-if="showModal"
      :staff="selectedStaff"
      @close="showModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useStaffStore } from '@/stores/staff';
import StatCard from './StatCard.vue';
import StaffList from './StaffList.vue';
import TopPerformers from './TopPerformers.vue';
import AIInsights from './AIInsights.vue';
import Leaderboard from './Leaderboard.vue';
import UpcomingShifts from './UpcomingShifts.vue';
import StaffModal from './StaffModal.vue';

const staffStore = useStaffStore();

const stats = ref({
  total: 0,
  active: 0,
  onShift: 0,
  onVacation: 0
});

const staff = ref([]);
const topPerformers = ref([]);
const aiInsights = ref([]);
const leaderboard = ref([]);
const upcomingShifts = ref([]);
const loading = ref(false);
const showModal = ref(false);
const selectedStaff = ref(null);

const loadStaff = async () => {
  loading.value = true;
  try {
    const response = await staffStore.fetchStaff();
    staff.value = response.data;
    stats.value = response.stats;
    topPerformers.value = response.topPerformers;
    aiInsights.value = response.aiInsights;
    leaderboard.value = response.leaderboard;
    upcomingShifts.value = response.upcomingShifts;
  } catch (error) {
    console.error('Failed to load staff:', error);
  } finally {
    loading.value = false;
  }
};

const setFilter = (filter: string) => {
  staffStore.setFilter(filter);
  loadStaff();
};

onMounted(() => {
  loadStaff();
});
</script>

<style scoped>
.staff-dashboard {
  padding: 24px;
}
</style>
