import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { ref } from 'vue'
import axios from 'axios'
import { useBarcodeScanner } from '../useBarcodeScanner'

// Mock axios
vi.mock('axios')

// Mock @zxing/library
vi.mock('@zxing/library', () => ({
  BrowserMultiFormatReader: vi.fn().mockImplementation(() => ({
    decodeFromCanvas: vi.fn(),
    reset: vi.fn(),
  })),
  BarcodeFormat: {
    QR_CODE: 'QR_CODE',
    EAN_13: 'EAN_13',
    EAN_8: 'EAN_8',
    UPC_A: 'UPC_A',
    CODE_128: 'CODE_128',
  },
}))

describe('useBarcodeScanner', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('validateQRCode', () => {
    const { validateQRCode } = useBarcodeScanner()

    it('validates valid QR code', () => {
      const result = validateQRCode('https://example.com')
      expect(result).toBe(true)
    })

    it('rejects too short QR code', () => {
      const result = validateQRCode('short')
      expect(result).toBe(false)
    })

    it('rejects too long QR code', () => {
      const result = validateQRCode('a'.repeat(3000))
      expect(result).toBe(false)
    })

    it('accepts minimum length QR code', () => {
      const result = validateQRCode('12345678')
      expect(result).toBe(true)
    })

    it('accepts maximum length QR code', () => {
      const result = validateQRCode('a'.repeat(2953))
      expect(result).toBe(true)
    })
  })

  describe('parseQRCodeData', () => {
    const { parseQRCodeData } = useBarcodeScanner()

    it('parses URL QR code', () => {
      const result = parseQRCodeData('https://example.com')
      expect(result.type).toBe('url')
      expect(result.data.url).toBe('https://example.com')
    })

    it('parses HTTP URL QR code', () => {
      const result = parseQRCodeData('http://example.com')
      expect(result.type).toBe('url')
      expect(result.data.url).toBe('http://example.com')
    })

    it('parses barcode in QR format', () => {
      const result = parseQRCodeData('1234567890123')
      expect(result.type).toBe('barcode')
      expect(result.data.barcode).toBe('1234567890123')
    })

    it('parses JSON QR code', () => {
      const jsonData = JSON.stringify({ id: 123, name: 'Test' })
      const result = parseQRCodeData(jsonData)
      expect(result.type).toBe('json')
      expect(result.data).toEqual({ id: 123, name: 'Test' })
    })

    it('handles invalid JSON gracefully', () => {
      const result = parseQRCodeData('{invalid json}')
      expect(result.type).toBe('text')
    })

    it('parses plain text QR code', () => {
      const result = parseQRCodeData('plain text')
      expect(result.type).toBe('text')
      expect(result.data.text).toBe('plain text')
    })
  })

  describe('stopCamera', () => {
    it('stops video stream', async () => {
      const mockStream = {
        getTracks: vi.fn(() => [{ stop: vi.fn() }]),
      } as any

      const { stopCamera, videoStream } = useBarcodeScanner()
      videoStream.value = mockStream

      stopCamera()

      expect(mockStream.getTracks).toHaveBeenCalled()
      expect(videoStream.value).toBeNull()
    })

    it('handles null stream gracefully', () => {
      const { stopCamera, videoStream } = useBarcodeScanner()
      videoStream.value = null

      expect(() => stopCamera()).not.toThrow()
    })
  })

  describe('validateBarcode', () => {
    it('validates EAN-13 barcode', async () => {
      const { validateBarcode } = useBarcodeScanner()
      vi.mocked(axios.post).mockResolvedValue({
        data: {
          original: '5901234123457',
          normalized: '5901234123457',
          valid: true,
          type: 'EAN13',
        },
      })

      const result = await validateBarcode('5901234123457')

      expect(result).toBe(true)
      expect(axios.post).toHaveBeenCalledWith('/api/wms/barcode/validate', {
        barcode: '5901234123457',
      })
    })

    it('handles validation error', async () => {
      const { validateBarcode } = useBarcodeScanner()
      vi.mocked(axios.post).mockRejectedValue(new Error('Network error'))

      const result = await validateBarcode('invalid')

      expect(result).toBe(false)
    })
  })

  describe('lookupBarcode', () => {
    it('looks up barcode successfully', async () => {
      const { lookupBarcode } = useBarcodeScanner()
      const mockResponse = {
        found: true,
        item_id: 123,
        sku: 'SKU001',
        name: 'Test Product',
        current_stock: 100,
      }

      vi.mocked(axios.get).mockResolvedValue({ data: mockResponse })

      const result = await lookupBarcode('5901234123457', 1)

      expect(result).toEqual(mockResponse)
      expect(axios.get).toHaveBeenCalledWith('/api/wms/barcode/lookup/5901234123457', {
        params: { warehouse_id: 1 },
      })
    })

    it('handles lookup error', async () => {
      const { lookupBarcode } = useBarcodeScanner()
      vi.mocked(axios.get).mockRejectedValue(new Error('Network error'))

      const result = await lookupBarcode('invalid', 1)

      expect(result.found).toBe(false)
    })
  })

  describe('preFlightScan', () => {
    it('validates pre-flight scan successfully', async () => {
      const { preFlightScan } = useBarcodeScanner()
      const mockResponse = {
        valid: true,
        can_proceed: true,
        item: {
          found: true,
          current_stock: 100,
        },
      }

      vi.mocked(axios.post).mockResolvedValue({ data: mockResponse })

      const result = await preFlightScan('5901234123457', 1, 'out', 10)

      expect(result).toEqual(mockResponse)
      expect(axios.post).toHaveBeenCalledWith('/api/wms/barcode/scan/preflight', {
        barcode: '5901234123457',
        warehouse_id: 1,
        operation: 'out',
        quantity: 10,
      })
    })

    it('handles pre-flight validation failure', async () => {
      const { preFlightScan } = useBarcodeScanner()
      const mockResponse = {
        valid: false,
        message: 'Insufficient stock',
      }

      vi.mocked(axios.post).mockResolvedValue({ data: mockResponse })

      const result = await preFlightScan('5901234123457', 1, 'out', 1000)

      expect(result.valid).toBe(false)
    })
  })

  describe('scanAndAdjust', () => {
    it('processes scan and adjustment successfully', async () => {
      const { scanAndAdjust } = useBarcodeScanner()
      const mockResponse = {
        success: true,
        movement_id: 456,
        new_stock: 110,
      }

      vi.mocked(axios.post).mockResolvedValue({ data: mockResponse })

      const result = await scanAndAdjust('5901234123457', 1, 10, 'in', 'Test')

      expect(result).toEqual(mockResponse)
      expect(axios.post).toHaveBeenCalledWith('/api/wms/barcode/scan/adjust', {
        barcode: '5901234123457',
        warehouse_id: 1,
        quantity: 10,
        adjustment_type: 'in',
        reason: 'Test',
      })
    })

    it('handles scan adjustment error', async () => {
      const { scanAndAdjust, error } = useBarcodeScanner()
      vi.mocked(axios.post).mockRejectedValue({
        response: {
          data: {
            message: 'Item not found',
          },
        },
      })

      const result = await scanAndAdjust('invalid', 1, 10, 'in', 'Test')

      expect(result.success).toBe(false)
      expect(result.message).toBe('Item not found')
    })
  })

  describe('bulkScanAndAdjust', () => {
    it('processes bulk scans successfully', async () => {
      const { bulkScanAndAdjust } = useBarcodeScanner()
      const mockResponse = {
        results: [
          { success: true, movement_id: 1 },
          { success: true, movement_id: 2 },
        ],
        total: 2,
        successful: 2,
        failed: 0,
      }

      vi.mocked(axios.post).mockResolvedValue({ data: mockResponse })

      const scans = [
        {
          barcode: '5901234123457',
          warehouse_id: 1,
          quantity: 10,
          adjustment_type: 'in' as const,
          reason: 'Test 1',
        },
        {
          barcode: '5901234123458',
          warehouse_id: 1,
          quantity: 5,
          adjustment_type: 'out' as const,
          reason: 'Test 2',
        },
      ]

      const result = await bulkScanAndAdjust(scans)

      expect(result).toEqual(mockResponse)
      expect(axios.post).toHaveBeenCalledWith('/api/wms/barcode/scan/bulk-adjust', {
        scans,
      })
    })

    it('handles bulk scan errors', async () => {
      const { bulkScanAndAdjust } = useBarcodeScanner()
      vi.mocked(axios.post).mockRejectedValue(new Error('Network error'))

      const scans = [
        {
          barcode: '5901234123457',
          warehouse_id: 1,
          quantity: 10,
          adjustment_type: 'in' as const,
          reason: 'Test',
        },
      ]

      const result = await bulkScanAndAdjust(scans)

      expect(result.failed).toBe(1)
    })
  })

  describe('getScannerHistory', () => {
    it('fetches scanner history successfully', async () => {
      const { getScannerHistory } = useBarcodeScanner()
      const mockResponse = {
        data: [
          {
            id: 1,
            type: 'in',
            quantity: 10,
            created_at: '2026-04-28T12:00:00Z',
          },
        ],
        total: 1,
      }

      vi.mocked(axios.get).mockResolvedValue({ data: mockResponse })

      const result = await getScannerHistory(1)

      expect(result).toEqual(mockResponse)
      expect(axios.get).toHaveBeenCalledWith('/api/wms/barcode/scan/history', {
        params: { warehouse_id: 1 },
      })
    })

    it('fetches history with date filters', async () => {
      const { getScannerHistory } = useBarcodeScanner()
      const mockResponse = { data: [], total: 0 }

      vi.mocked(axios.get).mockResolvedValue({ data: mockResponse })

      const result = await getScannerHistory(1, '2026-04-01', '2026-04-30')

      expect(axios.get).toHaveBeenCalledWith('/api/wms/barcode/scan/history', {
        params: {
          warehouse_id: 1,
          from_date: '2026-04-01',
          to_date: '2026-04-30',
        },
      })
    })

    it('handles history fetch error', async () => {
      const { getScannerHistory } = useBarcodeScanner()
      vi.mocked(axios.get).mockRejectedValue(new Error('Network error'))

      const result = await getScannerHistory(1)

      expect(result).toBeNull()
    })
  })
})
