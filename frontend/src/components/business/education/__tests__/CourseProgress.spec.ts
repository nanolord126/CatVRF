import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CourseProgress from '../CourseProgress.vue'
import axios from 'axios'

vi.mock('axios')

describe('CourseProgress', () => {
  const mockMilestones = [
    { id: 1, module_title: 'Module 1', amount_rub: 5000, status: 'paid' as const },
    { id: 2, module_title: 'Module 2', amount_rub: 5000, status: 'held' as const },
    { id: 3, module_title: 'Module 3', amount_rub: 5000, status: 'pending' as const },
  ]

  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.setItem('token', 'test-token')
  })

  it('renders loading state initially', () => {
    const wrapper = mount(CourseProgress, {
      props: { enrollmentId: 1 }
    })
    expect(wrapper.find('.loading').text()).toBe('Loading payment milestones...')
  })

  it('fetches and displays milestones', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockMilestones })

    const wrapper = mount(CourseProgress, {
      props: { enrollmentId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(axios.get).toHaveBeenCalledWith(
      '/api/v1/education/payments/milestones/1',
      expect.objectContaining({
        headers: expect.objectContaining({
          'Authorization': 'Bearer test-token'
        })
      })
    )
    expect(wrapper.findAll('.milestone-item').length).toBe(3)
  })

  it('calculates total paid amount correctly', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockMilestones })

    const wrapper = mount(CourseProgress, {
      props: { enrollmentId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.text()).toContain('50.00 ₽')
  })

  it('displays correct status classes for milestones', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockMilestones })

    const wrapper = mount(CourseProgress, {
      props: { enrollmentId: 1 }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    const statusBadges = wrapper.findAll('.status')
    expect(statusBadges[0].classes()).toContain('status-paid')
    expect(statusBadges[1].classes()).toContain('status-held')
    expect(statusBadges[2].classes()).toContain('status-pending')
  })
})
