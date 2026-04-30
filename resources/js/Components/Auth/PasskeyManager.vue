<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

interface Credential {
  id: number;
  name: string;
  device_type: string;
  device_type_label: string;
  backed_up: boolean;
  is_platform_authenticator: boolean;
  is_synced: boolean;
  transports: string[];
  last_used_at: string | null;
  created_at: string;
  user_agent: string | null;
  ip_address: string | null;
}

interface CredentialStats {
  total: number;
  platform: number;
  syncable: number;
  single_device: number;
  last_used: string | null;
}

const emit = defineEmits<{
  refresh: [];
}>();

const loading = ref(false);
const error = ref('');
const credentials = ref<Credential[]>([]);
const stats = ref<CredentialStats | null>(null);
const editingCredential = ref<Credential | null>(null);
const newName = ref('');
const deletingCredential = ref<Credential | null>(null);

const authStore = useAuthStore();

onMounted(() => {
  loadCredentials();
});

async function loadCredentials() {
  loading.value = true;
  error.value = '';

  try {
    const token = authStore.token;
    
    if (!token) {
      throw new Error('You must be logged in');
    }

    const response = await fetch('/api/v1/auth/passkey/credentials', {
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });

    if (!response.ok) {
      throw new Error('Failed to load credentials');
    }

    const data = await response.json();
    credentials.value = data.credentials;
    stats.value = data.stats;
  } catch (err: any) {
    error.value = err.message || 'Failed to load credentials';
  } finally {
    loading.value = false;
  }
}

async function renameCredential() {
  if (!editingCredential.value || !newName.value) return;

  loading.value = true;
  error.value = '';

  try {
    const token = authStore.token;
    
    const response = await fetch(`/api/v1/auth/passkey/credentials/${editingCredential.value.id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ name: newName.value }),
    });

    if (!response.ok) {
      throw new Error('Failed to rename credential');
    }

    // Update local state
    const index = credentials.value.findIndex(c => c.id === editingCredential.value!.id);
    if (index !== -1) {
      credentials.value[index].name = newName.value;
    }

    editingCredential.value = null;
    newName.value = '';
  } catch (err: any) {
    error.value = err.message || 'Failed to rename credential';
  } finally {
    loading.value = false;
  }
}

async function deleteCredential() {
  if (!deletingCredential.value) return;

  loading.value = true;
  error.value = '';

  try {
    const token = authStore.token;
    
    const response = await fetch(`/api/v1/auth/passkey/credentials/${deletingCredential.value.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ revoke_sessions: true }),
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error || 'Failed to delete credential');
    }

    // Remove from local state
    credentials.value = credentials.value.filter(c => c.id !== deletingCredential.value!.id);
    
    // Reload stats
    await loadCredentials();
    
    deletingCredential.value = null;
  } catch (err: any) {
    error.value = err.message || 'Failed to delete credential';
  } finally {
    loading.value = false;
  }
}

function startEdit(credential: Credential) {
  editingCredential.value = credential;
  newName.value = credential.name;
}

function cancelEdit() {
  editingCredential.value = null;
  newName.value = '';
}

function confirmDelete(credential: Credential) {
  deletingCredential.value = credential;
}

function cancelDelete() {
  deletingCredential.value = null;
}

function getDeviceIcon(credential: Credential): string {
  if (credential.is_platform_authenticator) {
    if (credential.user_agent?.includes('iPhone') || credential.user_agent?.includes('iPad')) {
      return '🍎';
    }
    if (credential.user_agent?.includes('Mac')) {
      return '🍎';
    }
    if (credential.user_agent?.includes('Windows')) {
      return '🪟';
    }
    if (credential.user_agent?.includes('Android')) {
      return '🤖';
    }
  }
  return '🔑';
}

function formatDate(dateString: string | null): string {
  if (!dateString) return 'Never';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>

<template>
  <div class="passkey-manager">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold text-gray-900">Your Passkeys</h2>
      <button
        @click="$emit('refresh')"
        class="text-sm text-blue-600 hover:text-blue-500"
      >
        Refresh
      </button>
    </div>

    <div v-if="error" class="alert alert-error">
      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
      </svg>
      <span>{{ error }}</span>
    </div>

    <!-- Stats -->
    <div v-if="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
        <div class="text-sm text-gray-500">Total</div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div class="text-2xl font-bold text-blue-600">{{ stats.platform }}</div>
        <div class="text-sm text-gray-500">Platform</div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div class="text-2xl font-bold text-green-600">{{ stats.syncable }}</div>
        <div class="text-sm text-gray-500">Synced</div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div class="text-sm font-medium text-gray-900 truncate">{{ formatDate(stats.last_used) }}</div>
        <div class="text-sm text-gray-500">Last Used</div>
      </div>
    </div>

    <!-- Credentials List -->
    <div v-if="loading" class="text-center py-8">
      <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <p class="mt-2 text-gray-500">Loading passkeys...</p>
    </div>

    <div v-else-if="credentials.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No passkeys registered</h3>
      <p class="mt-1 text-sm text-gray-500">Add a passkey to enable passwordless authentication.</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="credential in credentials"
        :key="credential.id"
        class="bg-white p-4 rounded-lg shadow-sm border border-gray-200"
      >
        <div class="flex items-start justify-between">
          <div class="flex items-start space-x-3">
            <div class="text-2xl">{{ getDeviceIcon(credential) }}</div>
            <div>
              <!-- Edit Mode -->
              <div v-if="editingCredential?.id === credential.id" class="flex items-center space-x-2">
                <input
                  v-model="newName"
                  type="text"
                  class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  @keyup.enter="renameCredential"
                />
                <button
                  @click="renameCredential"
                  :disabled="loading"
                  class="text-green-600 hover:text-green-700"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </button>
                <button
                  @click="cancelEdit"
                  class="text-gray-600 hover:text-gray-700"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              
              <!-- View Mode -->
              <div v-else>
                <h3 class="text-sm font-medium text-gray-900">{{ credential.name }}</h3>
                <p class="text-xs text-gray-500 mt-1">
                  {{ credential.device_type_label }}
                  <span v-if="credential.is_synced" class="ml-2 text-green-600">• Synced</span>
                </p>
                <p class="text-xs text-gray-400 mt-1">
                  Added: {{ formatDate(credential.created_at) }}
                </p>
                <p class="text-xs text-gray-400">
                  Last used: {{ formatDate(credential.last_used_at) }}
                </p>
              </div>
            </div>
          </div>

          <div v-if="editingCredential?.id !== credential.id" class="flex space-x-2">
            <button
              @click="startEdit(credential)"
              class="text-gray-400 hover:text-gray-600"
              title="Rename"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              @click="confirmDelete(credential)"
              class="text-gray-400 hover:text-red-600"
              title="Delete"
              :disabled="credentials.length === 1"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deletingCredential" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Passkey?</h3>
        <p class="text-sm text-gray-500 mb-4">
          Are you sure you want to delete "{{ deletingCredential.name }}"? This action cannot be undone.
        </p>
        <p v-if="credentials.length === 1" class="text-sm text-red-600 mb-4">
          Warning: This is your last passkey. You will need to use password authentication after deletion.
        </p>
        <div class="flex justify-end space-x-3">
          <button
            @click="cancelDelete"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
          >
            Cancel
          </button>
          <button
            @click="deleteCredential"
            :disabled="loading"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50"
          >
            Delete
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.alert {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  border-radius: 0.375rem;
  margin-bottom: 1rem;
}

.alert-error {
  background-color: #fee2e2;
  color: #991b1b;
}

.alert svg {
  flex-shrink: 0;
  margin-right: 0.5rem;
}
</style>
