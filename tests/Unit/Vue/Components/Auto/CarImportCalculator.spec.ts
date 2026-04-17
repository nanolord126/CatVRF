import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import CarImportCalculator from '@/Components/Business/Auto/CarImportCalculator.vue';

describe('CarImportCalculator', () => {
  it('renders calculator form', () => {
    const wrapper = mount(CarImportCalculator);
    expect(wrapper.find('input[placeholder*="VIN"]').exists()).toBe(true);
  });

  it('has currency selector', () => {
    const wrapper = mount(CarImportCalculator);
    expect(wrapper.find('select').exists()).toBe(true);
  });

  it('has engine type selector', () => {
    const wrapper = mount(CarImportCalculator);
    const selects = wrapper.findAll('select');
    expect(selects.length).toBeGreaterThanOrEqual(2);
  });
});
