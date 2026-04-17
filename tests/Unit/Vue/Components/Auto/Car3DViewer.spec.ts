import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import Car3DViewer from '@/Components/Business/Auto/Car3DViewer.vue';

describe('Car3DViewer', () => {
  it('renders correctly', () => {
    const wrapper = mount(Car3DViewer, {
      props: {
        modelUrl: '/models/car.glb',
        autoRotate: true,
        backgroundColor: '#1a1a1a',
      },
    });
    expect(wrapper.find('.car-3d-viewer').exists()).toBe(true);
  });

  it('emits modelLoaded event', async () => {
    const wrapper = mount(Car3DViewer, {
      props: { modelUrl: '/models/car.glb' },
    });
    await wrapper.vm.handleModelLoad();
    expect(wrapper.emitted('modelLoaded')).toBeTruthy();
  });

  it('emits modelError event', async () => {
    const wrapper = mount(Car3DViewer, {
      props: { modelUrl: '/models/car.glb' },
    });
    await wrapper.vm.handleModelError();
    expect(wrapper.emitted('modelError')).toBeTruthy();
  });
});
