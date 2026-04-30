<template>
  <div class="cold-chain-monitor">
    <div class="monitor-header">
      <div>
        <h1>Cold Chain Monitoring</h1>
        <p class="subtitle">Real-time temperature monitoring (ФЗ-323 Compliance)</p>
      </div>
      <div class="header-actions">
        <select v-model="selectedWarehouse" @change="loadWarehouseData" class="select">
          <option value="">All Warehouses</option>
          <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
            {{ warehouse.name }}
          </option>
        </select>
        <button @click="startMonitoring" class="btn btn-primary" :disabled="isMonitoring">
          <Play v-if="!isMonitoring" class="w-4 h-4 mr-2" />
          <Pause v-else class="w-4 h-4 mr-2" />
          {{ isMonitoring ? 'Pause' : 'Start' }}
        </button>
      </div>
    </div>

    <div class="alerts-section" v-if="activeAlerts.length > 0">
      <div class="alert alert-critical" v-for="alert in activeAlerts" :key="alert.id">
        <AlertTriangle class="w-5 h-5" />
        <div class="alert-content">
          <div class="alert-title">{{ alert.alert_type }} Violation</div>
          <div class="alert-message">
            {{ alert.warehouse_name }} - Zone {{ alert.zone_id }}: 
            {{ alert.current_temperature }}°C (limit: {{ alert.max_temp }}°C)
          </div>
        </div>
        <div class="alert-actions">
          <button @click="resolveAlert(alert)" class="btn btn-sm btn-success">
            Resolve
          </button>
          <button @click="escalateAlert(alert)" class="btn btn-sm btn-warning">
            Escalate
          </button>
        </div>
      </div>
    </div>

    <div class="monitor-grid">
      <div class="card">
        <div class="card-header">
          <h2>Temperature Readings</h2>
          <span class="badge badge-success" v-if="compliancePercentage >= 95">
            {{ compliancePercentage }}% Compliant
          </span>
          <span class="badge badge-warning" v-else-if="compliancePercentage >= 80">
            {{ compliancePercentage }}% Compliant
          </span>
          <span class="badge badge-danger" v-else>
            {{ compliancePercentage }}% Compliant
          </span>
        </div>
        <div class="card-body">
          <TemperatureChart :data="temperatureData" :loading="loading" />
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Humidity Levels</h2>
        </div>
        <div class="card-body">
          <HumidityChart :data="humidityData" :loading="loading" />
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h2>Sensor Status</h2>
      </div>
      <div class="card-body">
        <div class="sensors-grid">
          <div 
            v-for="sensor in sensors" 
            :key="sensor.id" 
            class="sensor-card"
            :class="{ 'sensor-offline': !sensor.online }"
          >
            <div class="sensor-icon">
              <Wifi v-if="sensor.online" class="w-6 h-6 text-green-500" />
              <WifiOff v-else class="w-6 h-6 text-red-500" />
            </div>
            <div class="sensor-info">
              <div class="sensor-name">{{ sensor.name }}</div>
              <div class="sensor-details">
                <span>{{ sensor.temperature }}°C</span>
                <span>{{ sensor.humidity }}%</span>
              </div>
              <div class="sensor-zone">Zone {{ sensor.zone_id }}</div>
            </div>
            <div class="sensor-status" :class="sensor.status">
              {{ sensor.status }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h2>Compliance Report</h2>
        <button @click="generateReport" class="btn btn-sm btn-secondary">
          <Download class="w-4 h-4 mr-2" />
          Download Report
        </button>
      </div>
      <div class="card-body">
        <ComplianceReport :data="complianceData" :loading="loading" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Play, Pause, AlertTriangle, Wifi, WifiOff, Download } from 'lucide-vue-next';
import TemperatureChart from './TemperatureChart.vue';
import HumidityChart from './HumidityChart.vue';
import ComplianceReport from './ComplianceReport.vue';
import { useWMSApi } from '@/composables/useWMSApi';

const { getWarehouses, getColdChainAlerts, getColdChainReadings, getComplianceReport } = useWMSApi();

const loading = ref(false);
const isMonitoring = ref(false);
const selectedWarehouse = ref('');
const warehouses = ref([]);
const activeAlerts = ref([]);
const sensors = ref([]);
const temperatureData = ref([]);
const humidityData = ref([]);
const complianceData = ref({});
const compliancePercentage = ref(100);

let monitoringInterval: NodeJS.Timeout | null = null;

const loadWarehouseData = async () => {
  loading.value = true;
  try {
    const [alertsData, readingsData, reportData] = await Promise.all([
      getColdChainAlerts(selectedWarehouse.value),
      getColdChainReadings(selectedWarehouse.value),
      getComplianceReport(selectedWarehouse.value),
    ]);

    activeAlerts.value = alertsData.filter((a: any) => a.status === 'active');
    sensors.value = readingsData.sensors || [];
    temperatureData.value = readingsData.temperature || [];
    humidityData.value = readingsData.humidity || [];
    complianceData.value = reportData;
    compliancePercentage.value = reportData.compliance_percentage || 100;
  } catch (error) {
    console.error('Failed to load warehouse data:', error);
  } finally {
    loading.value = false;
  }
};

const startMonitoring = () => {
  if (isMonitoring.value) {
    stopMonitoring();
    return;
  }

  isMonitoring.value = true;
  loadWarehouseData();
  monitoringInterval = setInterval(loadWarehouseData, 30000); // Update every 30 seconds
};

const stopMonitoring = () => {
  isMonitoring.value = false;
  if (monitoringInterval) {
    clearInterval(monitoringInterval);
    monitoringInterval = null;
  }
};

const resolveAlert = async (alert: any) => {
  try {
    // API call to resolve alert
    await fetch(`/api/wms/cold-chain/alerts/${alert.id}/resolve`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ resolution: 'Resolved manually' }),
    });
    loadWarehouseData();
  } catch (error) {
    console.error('Failed to resolve alert:', error);
  }
};

const escalateAlert = async (alert: any) => {
  try {
    await fetch(`/api/wms/cold-chain/alerts/${alert.id}/escalate`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
    });
    loadWarehouseData();
  } catch (error) {
    console.error('Failed to escalate alert:', error);
  }
};

const generateReport = async () => {
  try {
    await getComplianceReport(selectedWarehouse.value, { download: true });
  } catch (error) {
    console.error('Failed to generate report:', error);
  }
};

onMounted(async () => {
  warehouses.value = await getWarehouses();
  loadWarehouseData();
});

onUnmounted(() => {
  stopMonitoring();
});
</script>

<style scoped>
.cold-chain-monitor {
  padding: 24px;
  background-color: #f8fafc;
  min-height: 100vh;
}

.monitor-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.monitor-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
}

.subtitle {
  color: #64748b;
  margin-top: 4px;
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.select {
  padding: 10px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  min-width: 200px;
}

.btn {
  display: inline-flex;
  align-items: center;
  padding: 10px 16px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}

.btn-secondary {
  background-color: #64748b;
  color: white;
}

.btn-success {
  background-color: #10b981;
  color: white;
}

.btn-warning {
  background-color: #f59e0b;
  color: white;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
}

.alerts-section {
  margin-bottom: 24px;
}

.alert {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 12px;
}

.alert-critical {
  background-color: #fef2f2;
  border: 1px solid #fecaca;
}

.alert-content {
  flex: 1;
}

.alert-title {
  font-weight: 600;
  color: #991b1b;
  margin-bottom: 4px;
}

.alert-message {
  color: #7f1d1d;
}

.alert-actions {
  display: flex;
  gap: 8px;
}

.monitor-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.card-header h2 {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
}

.card-body {
  padding: 20px;
}

.badge {
  padding: 4px 12px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 600;
}

.badge-success {
  background-color: #d1fae5;
  color: #065f46;
}

.badge-warning {
  background-color: #fef3c7;
  color: #92400e;
}

.badge-danger {
  background-color: #fee2e2;
  color: #991b1b;
}

.sensors-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

.sensor-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  transition: all 0.2s;
}

.sensor-card:hover {
  border-color: #3b82f6;
}

.sensor-offline {
  opacity: 0.5;
}

.sensor-info {
  flex: 1;
}

.sensor-name {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 4px;
}

.sensor-details {
  display: flex;
  gap: 12px;
  color: #64748b;
  font-size: 14px;
}

.sensor-zone {
  color: #94a3b8;
  font-size: 12px;
}

.sensor-status {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.sensor-status.online {
  background-color: #d1fae5;
  color: #065f46;
}

.sensor-status.warning {
  background-color: #fef3c7;
  color: #92400e;
}

.sensor-status.offline {
  background-color: #fee2e2;
  color: #991b1b;
}
</style>
