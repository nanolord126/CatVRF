import { ref } from 'vue';

export function useWMSApi() {
  const baseUrl = '/api/wms';
  const loading = ref(false);
  const error = ref<string | null>(null);

  const request = async (endpoint: string, options: RequestInit = {}) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await fetch(`${baseUrl}${endpoint}`, {
        ...options,
        headers: {
          'Content-Type': 'application/json',
          ...options.headers,
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      return await response.json();
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'An error occurred';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const getInventoryStats = async (params: any = {}) => {
    const queryString = new URLSearchParams(params).toString();
    return request(`/stats?${queryString}`);
  };

  const getRecentMovements = async () => {
    return request('/stock-movements?limit=10');
  };

  const getLowStockItems = async () => {
    return request('/inventory/low-stock');
  };

  const lookupBarcode = async (barcode: string) => {
    return request(`/barcode/lookup/${barcode}`);
  };

  const adjustStock = async (data: any) => {
    return request('/stock-movements', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  };

  const getWarehouses = async () => {
    return request('/warehouses');
  };

  const getColdChainAlerts = async (warehouseId: string | number = '') => {
    const endpoint = warehouseId ? `/cold-chain/alerts/${warehouseId}` : '/cold-chain/alerts';
    return request(endpoint);
  };

  const getColdChainReadings = async (warehouseId: string | number = '') => {
    const endpoint = warehouseId ? `/cold-chain/readings/${warehouseId}` : '/cold-chain/readings';
    return request(endpoint);
  };

  const getComplianceReport = async (warehouseId: string | number = '', params: any = {}) => {
    const queryString = new URLSearchParams(params).toString();
    const endpoint = warehouseId 
      ? `/cold-chain/compliance/${warehouseId}?${queryString}`
      : `/cold-chain/compliance?${queryString}`;
    return request(endpoint);
  };

  const getBatches = async (params: any = {}) => {
    const queryString = new URLSearchParams(params).toString();
    return request(`/batches?${queryString}`);
  };

  const selectBatchesFEFO = async (productId: number, warehouseId: number, quantity: number) => {
    return request('/batches/fefo', {
      method: 'POST',
      body: JSON.stringify({ product_id: productId, warehouse_id: warehouseId, quantity }),
    });
  };

  const getReorderRecommendations = async (tenantId: number) => {
    return request(`/inventory/reorder/${tenantId}`);
  };

  const generateABCXYZReport = async (tenantId: number) => {
    return request(`/reports/abc-xyz/${tenantId}`);
  };

  const generateExpiryReport = async (warehouseId: number) => {
    return request(`/reports/expiry/${warehouseId}`);
  };

  return {
    loading,
    error,
    getInventoryStats,
    getRecentMovements,
    getLowStockItems,
    lookupBarcode,
    adjustStock,
    getWarehouses,
    getColdChainAlerts,
    getColdChainReadings,
    getComplianceReport,
    getBatches,
    selectBatchesFEFO,
    getReorderRecommendations,
    generateABCXYZReport,
    generateExpiryReport,
  };
}
