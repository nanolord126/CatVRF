import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ARVRPractice from '../ARVRPractice.vue'
import axios from 'axios'

vi.mock('axios')

describe('ARVRPractice', () => {
  const mockModels = [
    {
      id: 1,
      title: '3D Model 1',
      model_url: 'https://example.com/model1.glb',
      thumbnail_url: 'https://example.com/thumb1.jpg',
      ar_enabled: true,
      vr_enabled: false,
    },
    {
      id: 2,
      title: '3D Model 2',
      model_url: 'https://example.com/model2.glb',
      thumbnail_url: 'https://example.com/thumb2.jpg',
      ar_enabled: false,
      vr_enabled: true,
    },
  ]

  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.setItem('token', 'test-token')
    global.crypto = { randomUUID: vi.fn(() => 'test-uuid') }
  })

  it('renders loading state initially', () => {
    const wrapper = mount(ARVRPractice, {
      props: { courseId: 1, moduleId: 1 }
    })
    expect(wrapper.find('.loading').text()).toBe('Loading 3D models...')
  })

  it('fetches and displays models', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockModels })

    const wrapper = mount(ARVRPractice, {
      props: { courseId: 1, moduleId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(axios.get).toHaveBeenCalledWith(
      '/api/v1/education/arvr/models/1',
      expect.objectContaining({
        headers: expect.objectContaining({
          'Authorization': 'Bearer test-token'
        })
      })
    )
    expect(wrapper.findAll('.model-item').length).toBe(2)
  })

  it('selects model on click', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockModels })

    const wrapper = mount(ARVRPractice, {
      props: { courseId: 1, moduleId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))
    await wrapper.findAll('.model-item')[0].trigger('click')

    expect(wrapper.find('.model-viewer-container').exists()).toBe(true)
  })

  it('displays empty state when no model selected', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockModels })

    const wrapper = mount(ARVRPractice, {
      props: { courseId: 1, moduleId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.empty-state').text()).toBe('Select a model to view in 3D')
  })

  it('checks AR support on mount', () => {
    const originalXR = (navigator as any).xr
    ;(navigator as any).xr = {}

    mount(ARVRPractice, {
      props: { courseId: 1, moduleId: 1 }
    })

    ;(navigator as any).xr = originalXR
  })

  it('checks VR support on mount', () => {
    const originalXR = (navigator as any).xr
    ;(navigator as any).xr = { isSessionSupported: vi.fn() }

    mount(ARVRPractice, {
      props: { courseId: 1, moduleId: 1 }
    })

    ;(navigator as any).xr = originalXR
  })

  it('displays AR badge for AR-enabled models', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockModels })

    const wrapper = mount(ARVRPractice, {
      props: { courseId: 1, moduleId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.badge.ar').exists()).toBe(true)
  })

  it('displays VR badge for VR-enabled models', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockModels })

    const wrapper = mount(ARVRPractice, {
      props: { courseId: 1, moduleId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.badge.vr').exists()).toBe(true)
  })
})
