import { ref, onUnmounted } from 'vue'
import axios from 'axios'
import { BrowserMultiFormatReader, BarcodeFormat } from '@zxing/library'

interface ScanResult {
  found: boolean
  item_id?: number
  sku?: string
  name?: string
  current_stock?: number
  barcode?: string
  message?: string
}

interface ScanAdjustmentResult {
  success: boolean
  movement_id?: number
  item_id?: number
  barcode?: string
  adjustment_type?: string
  quantity?: number
  previous_stock?: number
  new_stock?: number
  message?: string
}

export function useBarcodeScanner() {
  const isScanning = ref(false)
  const scanResult = ref<ScanResult | null>(null)
  const adjustmentResult = ref<ScanAdjustmentResult | null>(null)
  const error = ref<string | null>(null)
  const videoStream = ref<MediaStream | null>(null)
  const zxingReader = ref<BrowserMultiFormatReader | null>(null)

  /**
   * Initialize camera for scanning
   */
  const initializeCamera = async (videoElement: HTMLVideoElement): Promise<boolean> => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: 'environment', // Use back camera on mobile
          width: { ideal: 1280 },
          height: { ideal: 720 }
        }
      })
      
      videoStream.value = stream
      videoElement.srcObject = stream
      await videoElement.play()
      
      // Initialize ZXing reader
      if (!zxingReader.value) {
        zxingReader.value = new BrowserMultiFormatReader()
      }
      
      return true
    } catch (err) {
      error.value = 'Failed to access camera: ' + (err as Error).message
      return false
    }
  }

  /**
   * Stop camera stream
   */
  const stopCamera = () => {
    if (videoStream.value) {
      videoStream.value.getTracks().forEach(track => track.stop())
      videoStream.value = null
    }
    
    // Reset ZXing reader
    if (zxingReader.value) {
      zxingReader.value.reset()
    }
  }

  /**
   * Lookup barcode via API
   */
  const lookupBarcode = async (barcode: string, warehouseId: number = 1): Promise<ScanResult> => {
    try {
      const response = await axios.get(`/api/wms/barcode/lookup/${barcode}`, {
        params: { warehouse_id: warehouseId }
      })
      scanResult.value = response.data
      return response.data
    } catch (err) {
      error.value = 'Failed to lookup barcode'
      return { found: false, message: 'Lookup failed' }
    }
  }

  /**
   * Validate barcode before scanning
   */
  const validateBarcode = async (barcode: string): Promise<boolean> => {
    try {
      const response = await axios.post('/api/wms/barcode/validate', { barcode })
      return response.data.valid
    } catch (err) {
      return false
    }
  }

  /**
   * Pre-flight scan validation
   */
  const preFlightScan = async (
    barcode: string,
    warehouseId: number,
    operation: 'in' | 'out' | 'adjust',
    quantity: number
  ): Promise<{ valid: boolean; can_proceed?: boolean; message?: string }> => {
    try {
      const response = await axios.post('/api/wms/barcode/scan/preflight', {
        barcode,
        warehouse_id: warehouseId,
        operation,
        quantity
      })
      return response.data
    } catch (err) {
      return { valid: false, message: 'Pre-flight validation failed' }
    }
  }

  /**
   * Process scan and adjust stock
   */
  const scanAndAdjust = async (
    barcode: string,
    warehouseId: number,
    quantity: number,
    adjustmentType: 'in' | 'out' | 'adjust',
    reason: string
  ): Promise<ScanAdjustmentResult> => {
    isScanning.value = true
    error.value = null

    try {
      const response = await axios.post('/api/wms/barcode/scan/adjust', {
        barcode,
        warehouse_id: warehouseId,
        quantity,
        adjustment_type: adjustmentType,
        reason
      })
      
      adjustmentResult.value = response.data
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to process scan'
      return {
        success: false,
        message: error.value
      }
    } finally {
      isScanning.value = false
    }
  }

  /**
   * Bulk scan and adjust
   */
  const bulkScanAndAdjust = async (
    scans: Array<{
      barcode: string
      warehouse_id: number
      quantity: number
      adjustment_type: 'in' | 'out' | 'adjust'
      reason: string
    }>
  ) => {
    isScanning.value = true
    error.value = null

    try {
      const response = await axios.post('/api/wms/barcode/scan/bulk-adjust', { scans })
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to process bulk scan'
      return {
        results: [],
        total: 0,
        successful: 0,
        failed: scans.length
      }
    } finally {
      isScanning.value = false
    }
  }

  /**
   * Get scanner history
   */
  const getScannerHistory = async (warehouseId?: number, from_date?: string, to_date?: string) => {
    try {
      const params: any = {}
      if (warehouseId) params.warehouse_id = warehouseId
      if (from_date) params.from_date = from_date
      if (to_date) params.to_date = to_date

      const response = await axios.get('/api/wms/barcode/scan/history', { params })
      return response.data
    } catch (err) {
      error.value = 'Failed to fetch scanner history'
      return null
    }
  }

  /**
   * Detect barcode or QR code from video frame using canvas
   * Supports EAN-13, EAN-8, UPC-A, Code-128, and QR codes
   */
  const detectBarcodeFromFrame = async (
    videoElement: HTMLVideoElement,
    canvasElement: HTMLCanvasElement
  ): Promise<{ code: string | null; type: 'barcode' | 'qr' | null }> => {
    if (!videoElement.videoWidth || !videoElement.videoHeight) {
      return { code: null, type: null }
    }

    canvasElement.width = videoElement.videoWidth
    canvasElement.height = videoElement.videoHeight
    
    const ctx = canvasElement.getContext('2d')
    if (!ctx) return { code: null, type: null }

    ctx.drawImage(videoElement, 0, 0)
    
    // Use ZXing library for real barcode/QR detection
    if (!zxingReader.value) {
      zxingReader.value = new BrowserMultiFormatReader()
    }

    try {
      const result = await zxingReader.value.decodeFromCanvas(canvasElement)
      
      if (result) {
        const isQR = result.barcodeFormat === BarcodeFormat.QR_CODE
        return {
          code: result.text,
          type: isQR ? 'qr' : 'barcode'
        }
      }
    } catch (err) {
      // No barcode detected in this frame
      // This is normal during continuous scanning
    }
    
    return { code: null, type: null }
  }

  /**
   * Validate QR code format
   */
  const validateQRCode = (qrCode: string): boolean => {
    // Basic QR code validation
    // QR codes can contain various data formats (URLs, text, vCard, etc.)
    // Length typically between 8 and 2953 characters
    const length = qrCode.length
    return length >= 8 && length <= 2953
  }

  /**
   * Parse QR code data
   * QR codes may contain structured data like URLs, vCards, or custom formats
   */
  const parseQRCodeData = (qrCode: string): { type: string; data: any } => {
    // Check if it's a URL
    if (qrCode.startsWith('http://') || qrCode.startsWith('https://')) {
      return { type: 'url', data: { url: qrCode } }
    }

    // Check if it's a barcode in QR format
    if (/^\d{8,14}$/.test(qrCode)) {
      return { type: 'barcode', data: { barcode: qrCode } }
    }

    // Check for JSON data
    if (qrCode.startsWith('{') && qrCode.endsWith('}')) {
      try {
        const jsonData = JSON.parse(qrCode)
        return { type: 'json', data: jsonData }
      } catch {
        // Not valid JSON
      }
    }

    // Default: plain text
    return { type: 'text', data: { text: qrCode } }
  }

  // Cleanup on unmount
  onUnmounted(() => {
    stopCamera()
  })

  return {
    isScanning,
    scanResult,
    adjustmentResult,
    error,
    videoStream,
    initializeCamera,
    stopCamera,
    lookupBarcode,
    validateBarcode,
    preFlightScan,
    scanAndAdjust,
    bulkScanAndAdjust,
    getScannerHistory,
    detectBarcodeFromFrame,
    validateQRCode,
    parseQRCodeData
  }
}
