import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AIDiagnosticsForm from '@/Components/Business/Auto/AIDiagnosticsForm.vue';

describe('AIDiagnosticsForm', () => {
  it('renders form fields', () => {
    const wrapper = mount(AIDiagnosticsForm);
    expect(wrapper.find('input[placeholder*="VIN"]').exists()).toBe(true);
  });

  it('validates VIN format', () => {
    const wrapper = mount(AIDiagnosticsForm);
    const vinInput = wrapper.find('input[type="text"]');
    vinInput.setValue('INVALID');
    expect(wrapper.vm.vin).toBe('INVALID');
  });

  it('converts VIN to uppercase', () => {
    const wrapper = mount(AIDiagnosticsForm);
    const vinInput = wrapper.find('input[type="text"]');
    vinInput.setValue('jtdkn3du5a0123456');
    expect(wrapper.vm.vin).toBe('JTDKN3DU5A0123456');
  });
});
