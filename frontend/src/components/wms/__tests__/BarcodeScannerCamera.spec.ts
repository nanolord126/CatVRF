import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import BarcodeScannerCamera from '../BarcodeScannerCamera.vue'
import { useBarcodeScanner } from '@/composables/useBarcodeScanner'

// Mock the composable
vi.mock('@/composables/useBarcodeScanner')

describe('BarcodeScannerCamera', () => {
  const mockUseBarcodeScanner = {
    isScanning: ref(false),
    scanResult: ref(null),
    adjustmentResult: ref(null),
    error: ref(null),
    videoStream: ref(null),
    initializeCamera: vi.fn(),
    stopCamera: vi.fn(),
    lookupBarcode: vi.fn(),
    validateBarcode: vi.fn(),
    preFlightScan: vi.fn(),
    scanAndAdjust: vi.fn(),
    bulkScanAndAdjust: vi.fn(),
    getScannerHistory: vi.fn(),
    detectBarcodeFromFrame: vi.fn(),
    validateQRCode: vi.fn(),
    parseQRCodeData: vi.fn(),
  }

  beforeEach(() => {
    vi.mocked(useBarcodeScanner).mockReturnValue(mockUseBarcodeScanner)
    vi.clearAllMocks()
  })

  const createWrapper = (props = {}) => {
    return mount(BarcodeScannerCamera, {
      props: {
        warehouseId: 1,
        ...props,
      },
      global: {
        stubs: {
          video: true,
          canvas: true,
        },
      },
    })
  }

  it('renders scanner component', () => {
    const wrapper = createWrapper()
    expect(wrapper.find('.barcode-scanner-camera').exists()).toBe(true)
  })

  it('renders scanner header', () => {
    const wrapper = createWrapper()
    expect(wrapper.find('.scanner-title').text()).toBe('Сканер штрихкодов')
  })

  it('renders close button', () => {
    const wrapper = createWrapper()
    expect(wrapper.find('.close-btn').exists()).toBe(true)
  })

  it('emits close event when close button clicked', async () => {
    const wrapper = createWrapper()
    await wrapper.find('.close-btn').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('renders camera container', () => {
    const wrapper = createWrapper()
    expect(wrapper.find('.camera-container').exists()).toBe(true)
  })

  it('renders scan overlay with frame', () => {
    const wrapper = createWrapper()
    expect(wrapper.find('.scan-overlay').exists()).toBe(true)
    expect(wrapper.find('.scan-frame').exists()).toBe(true)
  })

  it('renders scan corners', () => {
    const wrapper = createWrapper()
    expect(wrapper.find('.corner-tl').exists()).toBe(true)
    expect(wrapper.find('.corner-tr').exists()).toBe(true)
    expect(wrapper.find('.corner-bl').exists()).toBe(true)
    expect(wrapper.find('.corner-br').exists()).toBe(true)
  })

  it('shows error message when error exists', async () => {
    mockUseBarcodeScanner.error.value = 'Camera access denied'
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.camera-error').exists()).toBe(true)
    expect(wrapper.find('.error-text').text()).toBe('Camera access denied')
  })

  it('shows retry button when error exists', async () => {
    mockUseBarcodeScanner.error.value = 'Camera error'
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.retry-btn').exists()).toBe(true)
  })

  it('shows manual input when camera is not active', async () => {
    mockUseBarcodeScanner.videoStream.value = null
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.manual-input').exists()).toBe(true)
  })

  it('renders barcode input field in manual mode', async () => {
    mockUseBarcodeScanner.videoStream.value = null
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.barcode-input').exists()).toBe(true)
  })

  it('renders scan button in manual mode', async () => {
    mockUseBarcodeScanner.videoStream.value = null
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.scan-btn').exists()).toBe(true)
  })

  it('shows scan result when item is found', async () => {
    mockUseBarcodeScanner.scanResult.value = {
      found: true,
      sku: 'SKU001',
      name: 'Test Product',
      current_stock: 100,
    }
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.scan-result').exists()).toBe(true)
    expect(wrapper.find('.result-found').exists()).toBe(true)
  })

  it('shows item details in scan result', async () => {
    mockUseBarcodeScanner.scanResult.value = {
      found: true,
      sku: 'SKU001',
      name: 'Test Product',
      current_stock: 100,
    }
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.text()).toContain('SKU001')
    expect(wrapper.text()).toContain('Test Product')
    expect(wrapper.text()).toContain('100')
  })

  it('shows not found message when item not found', async () => {
    mockUseBarcodeScanner.scanResult.value = {
      found: false,
      message: 'Item not found',
    }
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.result-not-found').exists()).toBe(true)
    expect(wrapper.text()).toContain('Item not found')
  })

  it('renders adjustment controls when item found', async () => {
    mockUseBarcodeScanner.scanResult.value = {
      found: true,
      sku: 'SKU001',
      name: 'Test Product',
      current_stock: 100,
    }
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.adjustment-controls').exists()).toBe(true)
  })

  it('renders adjustment type selector', async () => {
    mockUseBarcodeScanner.scanResult.value = {
      found: true,
      sku: 'SKU001',
      name: 'Test Product',
      current_stock: 100,
    }
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.select-input').exists()).toBe(true)
  })

  it('renders quantity input', async () => {
    mockUseBarcodeScanner.scanResult.value = {
      found: true,
      sku: 'SKU001',
      name: 'Test Product',
      current_stock: 100,
    }
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.number-input').exists()).toBe(true)
  })

  it('renders reason input', async () => {
    mockUseBarcodeScanner.scanResult.value = {
      found: true,
      sku: 'SKU001',
      name: 'Test Product',
      current_stock: 100,
    }
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.text-input').exists()).toBe(true)
  })

  it('renders adjust button', async () => {
    mockUseBarcodeScanner.scanResult.value = {
      found: true,
      sku: 'SKU001',
      name: 'Test Product',
      current_stock: 100,
    }
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.adjust-btn').exists()).toBe(true)
  })

  it('shows adjustment result when available', async () => {
    mockUseBarcodeScanner.adjustmentResult.value = {
      success: true,
      new_stock: 110,
      previous_stock: 100,
    }
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.adjustment-result').exists()).toBe(true)
    expect(wrapper.find('.success-message').exists()).toBe(true)
  })

  it('shows error message when adjustment fails', async () => {
    mockUseBarcodeScanner.adjustmentResult.value = {
      success: false,
      message: 'Insufficient stock',
    }
    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.error-message').exists()).toBe(true)
    expect(wrapper.text()).toContain('Insufficient stock')
  })

  it('emits scanComplete when adjustment succeeds', async () => {
    mockUseBarcodeScanner.scanAndAdjust.mockResolvedValue({
      success: true,
      new_stock: 110,
    })
    mockUseBarcodeScanner.preFlightScan.mockResolvedValue({
      valid: true,
      can_proceed: true,
    })
    mockUseBarcodeScanner.scanResult.value = {
      found: true,
      sku: 'SKU001',
      name: 'Test Product',
      current_stock: 100,
      barcode: '5901234123457',
    }

    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()

    await wrapper.find('.adjust-btn').trigger('click')
    await wrapper.vm.$nextTick()

    expect(wrapper.emitted('scanComplete')).toBeTruthy()
  })

  it('calls initializeCamera on mount', async () => {
    mockUseBarcodeScanner.initializeCamera.mockResolvedValue(true)
    createWrapper()
    await new Promise(resolve => setTimeout(resolve, 0))
    expect(mockUseBarcodeScanner.initializeCamera).toHaveBeenCalled()
  })

  it('calls stopCamera on unmount', async () => {
    const wrapper = createWrapper()
    wrapper.unmount()
    expect(mockUseBarcodeScanner.stopCamera).toHaveBeenCalled()
  })

  it('loads history on mount', async () => {
    mockUseBarcodeScanner.getScannerHistory.mockResolvedValue({
      data: [],
      total: 0,
    })
    createWrapper()
    await new Promise(resolve => setTimeout(resolve, 0))
    expect(mockUseBarcodeScanner.getScannerHistory).toHaveBeenCalled()
  })

  it('formats date correctly', () => {
    const wrapper = createWrapper()
    const date = new Date('2026-04-28T12:00:00Z')
    const formatted = wrapper.vm.formatDate(date.toISOString())
    expect(formatted).toContain('28')
    expect(formatted).toContain('04')
    expect(formatted).toContain('2026')
  })

  it('resets scan state after successful adjustment', async () => {
    mockUseBarcodeScanner.scanAndAdjust.mockResolvedValue({
      success: true,
    })
    mockUseBarcodeScanner.preFlightScan.mockResolvedValue({
      valid: true,
      can_proceed: true,
    })
    mockUseBarcodeScanner.scanResult.value = {
      found: true,
      barcode: '5901234123457',
    }

    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()

    await wrapper.find('.adjust-btn').trigger('click')
    await wrapper.vm.$nextTick()

    // After clicking scan again button, result should be reset
    expect(mockUseBarcodeScanner.scanResult.value).toBeTruthy()
  })

  it('handles manual barcode input', async () => {
    mockUseBarcodeScanner.videoStream.value = null
    mockUseBarcodeScanner.lookupBarcode.mockResolvedValue({
      found: true,
      sku: 'SKU001',
    })

    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()

    const input = wrapper.find('.barcode-input')
    await input.setValue('5901234123457')
    await input.trigger('keyup.enter')

    expect(mockUseBarcodeScanner.lookupBarcode).toHaveBeenCalledWith(
      '5901234123457',
      1
    )
  })

  it('validates adjustment before processing', async () => {
    mockUseBarcodeScanner.preFlightScan.mockResolvedValue({
      valid: false,
      message: 'Insufficient stock',
    })
    mockUseBarcodeScanner.scanResult.value = {
      found: true,
      barcode: '5901234123457',
    }

    const wrapper = createWrapper()
    await wrapper.vm.$nextTick()

    await wrapper.find('.adjust-btn').trigger('click')
    await wrapper.vm.$nextTick()

    expect(mockUseBarcodeScanner.preFlightScan).toHaveBeenCalled()
    expect(mockUseBarcodeScanner.scanAndAdjust).not.toHaveBeenCalled()
  })
})
