import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import SlotBooking from '../SlotBooking.vue'
import axios from 'axios'

vi.mock('axios')

describe('SlotBooking', () => {
  const mockSlots = [
    {
      id: 1,
      title: 'Webinar: Introduction',
      start_time: '2026-04-20T10:00:00Z',
      end_time: '2026-04-20T11:00:00Z',
      capacity: 100,
      booked_count: 50,
      slot_type: 'webinar' as const,
      status: 'available' as const
    },
    {
      id: 2,
      title: 'Tutoring Session',
      start_time: '2026-04-21T14:00:00Z',
      end_time: '2026-04-21T15:00:00Z',
      capacity: 1,
      booked_count: 0,
      slot_type: 'tutoring' as const,
      status: 'available' as const
    },
  ]

  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.setItem('token', 'test-token')
    localStorage.setItem('userId', '123')
  })

  it('renders loading state initially', () => {
    const wrapper = mount(SlotBooking, {
      props: { courseId: 1 }
    })
    expect(wrapper.find('.loading').text()).toBe('Loading slots...')
  })

  it('fetches and displays slots', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockSlots })

    const wrapper = mount(SlotBooking, {
      props: { courseId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(axios.get).toHaveBeenCalledWith(
      '/api/v1/education/slots/course/1',
      expect.objectContaining({
        headers: expect.objectContaining({
          'Authorization': 'Bearer test-token'
        })
      })
    )
    expect(wrapper.findAll('.slot-card').length).toBe(2)
  })

  it('displays empty state when no slots available', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: [] })

    const wrapper = mount(SlotBooking, {
      props: { courseId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.empty').text()).toBe('No available slots at the moment.')
  })

  it('holds slot when button clicked', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockSlots })
    vi.mocked(axios.post).mockResolvedValue({ data: {} })

    const wrapper = mount(SlotBooking, {
      props: { courseId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    await wrapper.find('.btn-hold').trigger('click')

    expect(axios.post).toHaveBeenCalledWith(
      '/api/v1/education/slots/1/hold',
      { user_id: '123' },
      expect.any(Object)
    )
  })

  it('shows booking modal after holding slot', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockSlots })
    vi.mocked(axios.post).mockResolvedValue({ data: {} })

    const wrapper = mount(SlotBooking, {
      props: { courseId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))
    await wrapper.find('.btn-hold').trigger('click')

    expect(wrapper.find('.booking-modal').exists()).toBe(true)
  })

  it('books slot with biometric hash', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockSlots })
    vi.mocked(axios.post).mockResolvedValue({ data: {} })

    const wrapper = mount(SlotBooking, {
      props: { courseId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))
    await wrapper.find('.btn-hold').trigger('click')
    
    await wrapper.setData({ biometricHash: 'test-hash-123' })
    await wrapper.find('.btn-confirm').trigger('click')

    expect(axios.post).toHaveBeenCalledWith(
      '/api/v1/education/slots/book',
      expect.objectContaining({
        biometric_hash: 'test-hash-123'
      }),
      expect.any(Object)
    )
  })
})
