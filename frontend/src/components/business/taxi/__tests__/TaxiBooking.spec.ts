import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import TaxiBooking from '../TaxiBooking.vue'
import axios from 'axios'

vi.mock('axios')

describe('TaxiBooking', () => {
  const mockDrivers = [
    {
      driver_id: 1,
      name: 'Driver 1',
      rating: 4.8,
      vehicle: { vehicle_class: 'economy', model: 'Toyota Camry' },
      distance_meters: 500,
      eta_minutes: 2,
      score: 0.85,
    },
    {
      driver_id: 2,
      name: 'Driver 2',
      rating: 4.5,
      vehicle: { vehicle_class: 'comfort', model: 'Hyundai Sonata' },
      distance_meters: 800,
      eta_minutes: 3,
      score: 0.75,
    },
  ]

  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.setItem('token', 'test-token')
    localStorage.setItem('userId', '123')
    global.crypto = { randomUUID: vi.fn(() => 'test-uuid') }
    global.navigator = { geolocation: { getCurrentPosition: vi.fn() } }
  })

  it('renders booking form', () => {
    const wrapper = mount(TaxiBooking)
    expect(wrapper.find('.taxi-booking').exists()).toBe(true)
    expect(wrapper.find('h2').text()).toBe('Book a Taxi')
  })

  it('finds drivers successfully', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockDrivers })

    const wrapper = mount(TaxiBooking)
    await wrapper.setData({
      pickup: { latitude: 55.7558, longitude: 37.6173, address: 'Moscow' },
      dropoff: { latitude: 55.7617, longitude: 37.6045, address: 'Red Square' },
    })
    await wrapper.find('.btn-find').trigger('click')

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.findAll('.driver-card').length).toBe(2)
  })

  it('selects driver on click', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockDrivers })

    const wrapper = mount(TaxiBooking)
    await wrapper.setData({
      pickup: { latitude: 55.7558, longitude: 37.6173, address: 'Moscow' },
      dropoff: { latitude: 55.7617, longitude: 37.6045, address: 'Red Square' },
    })
    await wrapper.find('.btn-find').trigger('click')

    await new Promise(resolve => setTimeout(resolve, 0))
    await wrapper.findAll('.driver-card')[0].trigger('click')

    expect(wrapper.vm.selectedDriver).toEqual(mockDrivers[0])
  })

  it('shows surge warning when multiplier > 1', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: { multiplier: 1.5 } })

    const wrapper = mount(TaxiBooking)
    await wrapper.setData({
      pickup: { latitude: 55.7558, longitude: 37.6173, address: 'Moscow' },
    })
    await wrapper.vm.fetchSurgeMultiplier()

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.surge-warning').exists()).toBe(true)
    expect(wrapper.find('.surge-warning').text()).toContain('150%')
  })

  it('changes vehicle class', async () => {
    const wrapper = mount(TaxiBooking)
    await wrapper.find('.vehicle-option').trigger('click')

    expect(wrapper.vm.vehicleClass).toBe('comfort')
  })
})
