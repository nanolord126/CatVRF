import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import LearningPathViewer from '../LearningPathViewer.vue'
import axios from 'axios'

vi.mock('axios')

describe('LearningPathViewer', () => {
  const mockLearningPath = {
    path_id: 'path-123',
    modules: [
      { id: 1, title: 'Module 1', description: 'Introduction', completed: true, estimated_hours: 5, difficulty: 'beginner' },
      { id: 2, title: 'Module 2', description: 'Advanced', completed: false, estimated_hours: 10, difficulty: 'advanced' },
    ],
    course_id: 101,
  }

  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.setItem('token', 'test-token')
  })

  it('renders loading state initially', () => {
    const wrapper = mount(LearningPathViewer, {
      global: {
        mocks: {
          $route: { params: { id: '1' } }
        }
      }
    })
    expect(wrapper.find('.loading').text()).toBe('Loading learning path...')
  })

  it('fetches and displays learning path', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockLearningPath })

    const wrapper = mount(LearningPathViewer, {
      global: {
        mocks: {
          $route: { params: { id: '1' } }
        }
      }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(axios.get).toHaveBeenCalledWith(
      '/api/v1/education/learning-paths/1',
      expect.objectContaining({
        headers: expect.objectContaining({
          'Authorization': 'Bearer test-token'
        })
      })
    )
    expect(wrapper.find('.path-content').exists()).toBe(true)
  })

  it('calculates progress percentage correctly', async () => {
    vi.mocked(axios.get).mockResolvedValue({ data: mockLearningPath })

    const wrapper = mount(LearningPathViewer, {
      global: {
        mocks: {
          $route: { params: { id: '1' } }
        }
      }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.text()).toContain('50% Complete')
  })

  it('displays error message on fetch failure', async () => {
    vi.mocked(axios.get).mockRejectedValue(new Error('Network error'))

    const wrapper = mount(LearningPathViewer, {
      global: {
        mocks: {
          $route: { params: { id: '1' } }
        }
      }
    })

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.error').text()).toBe('Failed to load learning path')
  })
})
