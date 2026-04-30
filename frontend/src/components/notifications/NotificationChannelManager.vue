<template>
  <div class="notification-channel-manager">
    <div class="header">
      <h2>Notification Channels</h2>
      <button @click="openAddModal" class="btn btn-primary">
        Add Channel
      </button>
    </div>

    <div class="channels-grid">
      <div
        v-for="channel in channels"
        :key="channel.id"
        class="channel-card"
        :class="{ 'enabled': channel.enabled, 'disabled': !channel.enabled }"
      >
        <div class="channel-icon">
          <i :class="getIconClass(channel.icon)"></i>
        </div>
        <div class="channel-info">
          <h3>{{ channel.name }}</h3>
          <p class="status" :class="channel.enabled ? 'active' : 'inactive'">
            {{ channel.enabled ? 'Enabled' : 'Disabled' }}
          </p>
        </div>
        <div class="channel-actions">
          <button @click="viewChannel(channel)" class="btn btn-sm btn-secondary">
            View
          </button>
          <button @click="testChannel(channel)" class="btn btn-sm btn-info">
            Test
          </button>
          <button
            @click="toggleChannel(channel)"
            class="btn btn-sm"
            :class="channel.enabled ? 'btn-danger' : 'btn-success'"
          >
            {{ channel.enabled ? 'Disable' : 'Enable' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal for channel configuration -->
    <div v-if="showModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ modalMode === 'add' ? 'Add Channel' : 'Configure ' + selectedChannel?.name }}</h3>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div v-if="modalMode === 'add'" class="channel-selection">
            <h4>Select Channel Type</h4>
            <div class="channel-types">
              <div
                v-for="channel in availableChannels"
                :key="channel.id"
                @click="selectChannelType(channel)"
                class="channel-type-card"
              >
                <i :class="getIconClass(channel.icon)"></i>
                <span>{{ channel.name }}</span>
              </div>
            </div>
          </div>
          <div v-else class="channel-config">
            <h4>{{ selectedChannel?.name }} Configuration</h4>
            <form @submit.prevent="saveChannel">
              <div v-for="key in selectedChannel?.config_keys" :key="key" class="form-group">
                <label>{{ formatConfigKey(key) }}</label>
                <input
                  v-model="config[key]"
                  :type="isSensitiveKey(key) ? 'password' : 'text'"
                  class="form-control"
                  :placeholder="`Enter ${key}`"
                  required
                />
              </div>
              <div class="form-actions">
                <button type="button" @click="closeModal" class="btn btn-secondary">
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                  Save Configuration
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Test Modal -->
    <div v-if="showTestModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Test {{ selectedChannel?.name }}</h3>
          <button @click="closeTestModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="runTest">
            <div class="form-group">
              <label>Test Recipient</label>
              <input
                v-model="testRecipient"
                type="text"
                class="form-control"
                :placeholder="getRecipientPlaceholder(selectedChannel?.id)"
                required
              />
            </div>
            <div class="form-actions">
              <button type="button" @click="closeTestModal" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="testing">
                {{ testing ? 'Sending...' : 'Send Test' }}
              </button>
            </div>
          </form>
          <div v-if="testResult" class="test-result" :class="testResult.success ? 'success' : 'error'">
            <p>{{ testResult.message }}</p>
            <pre v-if="testResult.result">{{ JSON.stringify(testResult.result, null, 2) }}</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

interface Channel {
  id: string
  name: string
  icon: string
  enabled: boolean
  configured: boolean
  config_keys: string[]
}

const channels = ref<Channel[]>([])
const availableChannels = ref<Channel[]>([])
const showModal = ref(false)
const showTestModal = ref(false)
const modalMode = ref<'add' | 'config'>('add')
const selectedChannel = ref<Channel | null>(null)
const config = ref<Record<string, string>>({})
const testRecipient = ref('')
const testing = ref(false)
const testResult = ref<any>(null)

onMounted(async () => {
  await loadChannels()
})

const loadChannels = async () => {
  try {
    const response = await axios.get('/api/notifications/channels')
    channels.value = response.data.channels
    availableChannels.value = response.data.channels
  } catch (error) {
    console.error('Failed to load channels:', error)
  }
}

const openAddModal = () => {
  modalMode.value = 'add'
  showModal.value = true
}

const viewChannel = (channel: Channel) => {
  selectedChannel.value = channel
  modalMode.value = 'config'
  showModal.value = true
  loadChannelConfig(channel.id)
}

const selectChannelType = (channel: Channel) => {
  selectedChannel.value = channel
  modalMode.value = 'config'
  config.value = {}
}

const closeModal = () => {
  showModal.value = false
  selectedChannel.value = null
  config.value = {}
}

const closeTestModal = () => {
  showTestModal.value = false
  testRecipient.value = ''
  testResult.value = null
}

const loadChannelConfig = async (channelId: string) => {
  try {
    const response = await axios.get(`/api/notifications/channels/${channelId}`)
    config.value = response.data.channel.config
  } catch (error) {
    console.error('Failed to load channel config:', error)
  }
}

const saveChannel = async () => {
  try {
    await axios.post('/api/notifications/channels', {
      channel: selectedChannel.value?.id,
      config: config.value
    })
    closeModal()
    await loadChannels()
    alert('Channel configured successfully!')
  } catch (error) {
    console.error('Failed to save channel:', error)
    alert('Failed to configure channel')
  }
}

const testChannel = (channel: Channel) => {
  selectedChannel.value = channel
  testRecipient.value = ''
  testResult.value = null
  showTestModal.value = true
}

const runTest = async () => {
  testing.value = true
  testResult.value = null
  
  try {
    const response = await axios.post(
      `/api/notifications/channels/${selectedChannel.value?.id}/test`,
      { recipient: testRecipient.value }
    )
    testResult.value = response.data
  } catch (error) {
    testResult.value = {
      success: false,
      message: 'Test failed: ' + (error as any).message
    }
  } finally {
    testing.value = false
  }
}

const toggleChannel = async (channel: Channel) => {
  try {
    const action = channel.enabled ? 'disable' : 'enable'
    await axios.post(`/api/notifications/channels/${channel.id}/${action}`)
    await loadChannels()
  } catch (error) {
    console.error('Failed to toggle channel:', error)
    alert('Failed to toggle channel')
  }
}

const getIconClass = (icon: string) => {
  const icons: Record<string, string> = {
    telegram: 'fab fa-telegram',
    whatsapp: 'fab fa-whatsapp',
    viber: 'fab fa-viber',
    kakaotalk: 'fas fa-comment',
    signal: 'fab fa-signal',
    wechat: 'fab fa-weixin',
    vk: 'fab fa-vk',
    odnoklassniki: 'fas fa-users',
    email: 'fas fa-envelope',
    sms: 'fas fa-sms',
  }
  return icons[icon] || 'fas fa-bell'
}

const formatConfigKey = (key: string) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const isSensitiveKey = (key: string) => {
  const sensitiveKeys = ['token', 'secret', 'password', 'api_key', 'access_token']
  return sensitiveKeys.some(sk => key.includes(sk))
}

const getRecipientPlaceholder = (channelId?: string) => {
  const placeholders: Record<string, string> = {
    telegram: 'Enter chat ID (e.g., 123456789)',
    whatsapp: 'Enter phone number (e.g., 1234567890)',
    viber: 'Enter Viber ID',
    email: 'Enter email address',
    sms: 'Enter phone number',
  }
  return placeholders[channelId || ''] || 'Enter recipient'
}
</script>

<style scoped>
.notification-channel-manager {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.channels-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.channel-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 20px;
  transition: all 0.3s;
}

.channel-card.enabled {
  border-color: #10b981;
  background-color: #f0fdf4;
}

.channel-card.disabled {
  border-color: #e5e7eb;
  background-color: #f9fafb;
}

.channel-icon {
  font-size: 32px;
  margin-bottom: 15px;
}

.channel-info h3 {
  margin: 0 0 10px 0;
  font-size: 18px;
}

.status {
  font-size: 14px;
  margin: 0;
}

.status.active {
  color: #10b981;
}

.status.inactive {
  color: #6b7280;
}

.channel-actions {
  display: flex;
  gap: 10px;
  margin-top: 15px;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
}

.modal-body {
  padding: 20px;
}

.channel-types {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 15px;
}

.channel-type-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;
}

.channel-type-card:hover {
  border-color: #3b82f6;
  background-color: #eff6ff;
}

.channel-type-card i {
  font-size: 32px;
  margin-bottom: 10px;
  display: block;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 14px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.btn {
  padding: 8px 16px;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  font-size: 14px;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-secondary {
  background-color: #6b7280;
  color: white;
}

.btn-success {
  background-color: #10b981;
  color: white;
}

.btn-danger {
  background-color: #ef4444;
  color: white;
}

.btn-info {
  background-color: #06b6d4;
  color: white;
}

.btn-sm {
  padding: 4px 8px;
  font-size: 12px;
}

.test-result {
  margin-top: 20px;
  padding: 15px;
  border-radius: 4px;
}

.test-result.success {
  background-color: #f0fdf4;
  color: #166534;
}

.test-result.error {
  background-color: #fef2f2;
  color: #991b1b;
}

.test-result pre {
  background-color: #f3f4f6;
  padding: 10px;
  border-radius: 4px;
  overflow-x: auto;
}
</style>
