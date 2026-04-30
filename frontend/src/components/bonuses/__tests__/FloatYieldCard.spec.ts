import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import FloatYieldCard from '../FloatYieldCard.vue';
import { catfloatApi } from '@/services/catfloatApi';

// Mock the API
vi.mock('@/services/catfloatApi', () => ({
  catfloatApi: {
    getFloatYieldSummary: vi.fn(),
  },
}));

describe('FloatYieldCard', () => {
  const mockUserId = 1;
  const mockTenantId = 1;
  const mockSummary = {
    total_locked: 3000,
    today_yield: 0.36,
    monthly_yield: 10.8,
    expected_yield: 25.2,
    user_rate: 0.00012,
    platform_rate: 0.00018,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders loading state initially', () => {
    vi.mocked(catfloatApi.getFloatYieldSummary).mockImplementation(
      () => new Promise(() => {})
    );

    const wrapper = mount(FloatYieldCard, {
      props: {
        userId: mockUserId,
        tenantId: mockTenantId,
      },
    });

    expect(wrapper.find('.loading-state').exists()).toBe(true);
    expect(wrapper.find('.spinner').exists()).toBe(true);
    expect(wrapper.text()).toContain('Загрузка данных...');
  });

  it('renders yield data after successful load', async () => {
    vi.mocked(catfloatApi.getFloatYieldSummary).mockResolvedValue(mockSummary);

    const wrapper = mount(FloatYieldCard, {
      props: {
        userId: mockUserId,
        tenantId: mockTenantId,
      },
    });

    // Wait for async operation
    await new Promise(resolve => setTimeout(resolve, 0));
    await wrapper.vm.$nextTick();

    expect(wrapper.find('.loading-state').exists()).toBe(false);
    expect(wrapper.find('.yield-content').exists()).toBe(true);
    expect(wrapper.text()).toContain('3 000,00 ₽'); // total_locked
    expect(wrapper.text()).toContain('0,36 ₽'); // today_yield
    expect(wrapper.text()).toContain('10,80 ₽'); // monthly_yield
    expect(wrapper.text()).toContain('25,20 ₽'); // expected_yield
  });

  it('renders error state on API failure', async () => {
    vi.mocked(catfloatApi.getFloatYieldSummary).mockRejectedValue(
      new Error('Failed to fetch')
    );

    const wrapper = mount(FloatYieldCard, {
      props: {
        userId: mockUserId,
        tenantId: mockTenantId,
      },
    });

    await new Promise(resolve => setTimeout(resolve, 0));
    await wrapper.vm.$nextTick();

    expect(wrapper.find('.error-state').exists()).toBe(true);
    expect(wrapper.text()).toContain('Failed to fetch');
  });

  it('displays legal badge', () => {
    vi.mocked(catfloatApi.getFloatYieldSummary).mockResolvedValue(mockSummary);

    const wrapper = mount(FloatYieldCard, {
      props: {
        userId: mockUserId,
        tenantId: mockTenantId,
      },
    });

    expect(wrapper.find('.legal-badge').exists()).toBe(true);
    expect(wrapper.find('.legal-badge').text()).toContain('Только бонусы');
  });

  it('displays legal notice about RF law', async () => {
    vi.mocked(catfloatApi.getFloatYieldSummary).mockResolvedValue(mockSummary);

    const wrapper = mount(FloatYieldCard, {
      props: {
        userId: mockUserId,
        tenantId: mockTenantId,
      },
    });

    await new Promise(resolve => setTimeout(resolve, 0));
    await wrapper.vm.$nextTick();

    expect(wrapper.find('.legal-notice').exists()).toBe(true);
    expect(wrapper.find('.legal-notice').text()).toContain('По законам РФ');
    expect(wrapper.find('.legal-notice').text()).toContain('только в бонусных баллах');
  });

  it('formats currency correctly', async () => {
    vi.mocked(catfloatApi.getFloatYieldSummary).mockResolvedValue(mockSummary);

    const wrapper = mount(FloatYieldCard, {
      props: {
        userId: mockUserId,
        tenantId: mockTenantId,
      },
    });

    await new Promise(resolve => setTimeout(resolve, 0));
    await wrapper.vm.$nextTick();

    const statValues = wrapper.findAll('.stat-value');
    expect(statValues[0].text()).toMatch(/3 000,00 ₽/);
  });

  it('formats rates correctly', async () => {
    vi.mocked(catfloatApi.getFloatYieldSummary).mockResolvedValue(mockSummary);

    const wrapper = mount(FloatYieldCard, {
      props: {
        userId: mockUserId,
        tenantId: mockTenantId,
      },
    });

    await new Promise(resolve => setTimeout(resolve, 0));
    await wrapper.vm.$nextTick();

    const rateValues = wrapper.findAll('.rate-value');
    expect(rateValues[0].text()).toMatch(/0,0120%/); // user_rate
    expect(rateValues[1].text()).toMatch(/0,0180%/); // platform_rate
  });

  it('calls API with correct parameters', async () => {
    vi.mocked(catfloatApi.getFloatYieldSummary).mockResolvedValue(mockSummary);

    mount(FloatYieldCard, {
      props: {
        userId: mockUserId,
        tenantId: mockTenantId,
      },
    });

    await new Promise(resolve => setTimeout(resolve, 0));

    expect(catfloatApi.getFloatYieldSummary).toHaveBeenCalledWith(
      mockUserId,
      mockTenantId
    );
  });

  it('handles zero values correctly', async () => {
    const zeroSummary = {
      total_locked: 0,
      today_yield: 0,
      monthly_yield: 0,
      expected_yield: 0,
      user_rate: 0.00012,
      platform_rate: 0.00018,
    };

    vi.mocked(catfloatApi.getFloatYieldSummary).mockResolvedValue(zeroSummary);

    const wrapper = mount(FloatYieldCard, {
      props: {
        userId: mockUserId,
        tenantId: mockTenantId,
      },
    });

    await new Promise(resolve => setTimeout(resolve, 0));
    await wrapper.vm.$nextTick();

    expect(wrapper.text()).toContain('0,00 ₽');
  });

  it('displays all stat cards', async () => {
    vi.mocked(catfloatApi.getFloatYieldSummary).mockResolvedValue(mockSummary);

    const wrapper = mount(FloatYieldCard, {
      props: {
        userId: mockUserId,
        tenantId: mockTenantId,
      },
    });

    await new Promise(resolve => setTimeout(resolve, 0));
    await wrapper.vm.$nextTick();

    expect(wrapper.find('.stat-card.total-locked').exists()).toBe(true);
    expect(wrapper.find('.stat-card.today-yield').exists()).toBe(true);
    expect(wrapper.find('.stat-card.monthly-yield').exists()).toBe(true);
    expect(wrapper.find('.stat-card.expected-yield').exists()).toBe(true);
  });
});
