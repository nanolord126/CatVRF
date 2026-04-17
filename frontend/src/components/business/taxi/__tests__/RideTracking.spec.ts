import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import RideTracking from '../RideTracking.vue'
import axios from 'axios'

vi.mock('axios')

describe('RideTracking', () => {
  const mockRideStatus = {
    id: 1,
    status: 'in_progress',
    driver: { name: 'Driver 1', rating: 4.8 },
    vehicle: { vehicle_class: 'economy', model: 'Toyota Camry', license_plate: 'А123БВ777', color: '#ffffff' },
    current_latitude: 55.7558,
    current_longitude: 37.6173,
    eta_minutes: 5,
    distance_remaining_meters: 2000,
  }

  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.setItem('token', 'test-token')
    global.crypto = { randomUUID: vi.fn(() => 'test-uuid') }
  })

  it('renders loading state initially', () => {
    const wrapper = mount(RideTracking, {
      props: { rideId: 1 }
    })
    expect(wrapper.find('.loading').text()).toBe('Loading ride status...')
  })

  it('fetches and displays ride status', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockRideStatus })

    const wrapper = mount(RideTracking, {
      props: { rideId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(axios.get).toHaveBeenCalledWith(
      '/api/v1/taxi/rides/1/status',
      expect.any(Object)
    )
    expect(wrapper.find('.status-badge').text()).toBe('In progress')
  })

  it('displays driver information', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockRideStatus })

    const wrapper = mount(RideTracking, {
      props: { rideId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.driver-details h3').text()).toBe('Driver 1')
    expect(wrapper.text()).toContain('4.8')
  })

  it('displays vehicle information', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockRideStatus })

    const wrapper = mount(RideTracking, {
      props: { rideId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.vehicle-model').text()).toBe('Toyota Camry')
    expect(wrapper.find('.vehicle-plate').text()).toBe('А123БВ777')
  })

  it('shows correct status color for in_progress', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockRideStatus })

    const wrapper = mount(RideTracking, {
      props: { rideId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.status-badge').attributes('style')).toContain('#10b981')
  })

  it('shows cancel button for active rides', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockRideStatus })

    const wrapper = mount(RideTracking, {
      props: { rideId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.btn-cancel').exists()).toBe(true)
  })
})
