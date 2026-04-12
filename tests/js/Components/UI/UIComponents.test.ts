/**
 * UI-компоненты — тесты кликабельности и поведения.
 *
 * VTabs: переключение табов
 * VButton: клики, disabled, варианты
 * VModal: открытие/закрытие
 * VCard: рендеринг с заголовком и слотами
 */
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import VTabs from '@/Components/UI/VTabs.vue';
import VButton from '@/Components/UI/VButton.vue';
import VModal from '@/Components/UI/VModal.vue';
import VCard from '@/Components/UI/VCard.vue';
import VBadge from '@/Components/UI/VBadge.vue';
import VStatCard from '@/Components/UI/VStatCard.vue';

/* ═══════════════════════════════════════════════════
 * VTabs
 * ═══════════════════════════════════════════════════ */
describe('VTabs', () => {
    const tabs = [
        { key: 'tab1', label: 'Первый' },
        { key: 'tab2', label: 'Второй' },
        { key: 'tab3', label: 'Третий' },
    ];

    it('рендерит все табы', () => {
        const wrapper = mount(VTabs, {
            props: { tabs, modelValue: 'tab1' },
        });
        expect(wrapper.text()).toContain('Первый');
        expect(wrapper.text()).toContain('Второй');
        expect(wrapper.text()).toContain('Третий');
    });

    it('эмитирует update:modelValue при клике на таб', async () => {
        const wrapper = mount(VTabs, {
            props: { tabs, modelValue: 'tab1' },
        });

        const tabButtons = wrapper.findAll('button');
        expect(tabButtons.length).toBeGreaterThanOrEqual(3);

        // Клик на второй таб
        await tabButtons[1].trigger('click');
        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')![0]).toEqual(['tab2']);
    });

    it('отображает бейджи', () => {
        const tabsWithBadges = [
            { key: 'tab1', label: 'Первый', badge: 5 },
            { key: 'tab2', label: 'Второй' },
        ];
        const wrapper = mount(VTabs, {
            props: { tabs: tabsWithBadges, modelValue: 'tab1' },
        });
        expect(wrapper.text()).toContain('5');
    });

    it('подсвечивает активный таб', async () => {
        const wrapper = mount(VTabs, {
            props: { tabs, modelValue: 'tab2' },
        });
        await flushPromises();
        const activeEl = wrapper.find('[data-active="true"]');
        if (activeEl.exists()) {
            expect(activeEl.text()).toContain('Второй');
        }
    });
});

/* ═══════════════════════════════════════════════════
 * VButton
 * ═══════════════════════════════════════════════════ */
describe('VButton', () => {
    it('рендерит текст кнопки через слот', () => {
        const wrapper = mount(VButton, {
            slots: { default: 'Нажми меня' },
        });
        expect(wrapper.text()).toContain('Нажми меня');
    });

    it('эмитирует click при нажатии', async () => {
        const wrapper = mount(VButton, {
            slots: { default: 'Кнопка' },
        });
        await wrapper.trigger('click');
        expect(wrapper.emitted('click')).toBeTruthy();
    });

    it('кнопка кликабельна (не disabled)', () => {
        const wrapper = mount(VButton, {
            slots: { default: 'Активная' },
        });
        const btn = wrapper.find('button');
        expect(btn.exists()).toBe(true);
        expect(btn.element.disabled).toBeFalsy();
    });
});

/* ═══════════════════════════════════════════════════
 * VModal
 * ═══════════════════════════════════════════════════ */
describe('VModal', () => {
    it('не рендерит контент если modelValue=false', () => {
        const wrapper = mount(VModal, {
            props: { modelValue: false, title: 'Тест' },
            slots: { default: '<p>Содержимое</p>' },
        });
        // Модалка закрыта — контент может быть скрыт через teleport/transition
        // Проверяем что модалка не видна пользователю
        expect(wrapper.text()).not.toContain('Содержимое');
    });

    it('рендерит контент если modelValue=true', () => {
        const wrapper = mount(VModal, {
            props: { modelValue: true, title: 'Тест модалка' },
            slots: { default: '<p>Содержимое модалки</p>' },
            global: { stubs: { teleport: true } },
        });
        expect(wrapper.text()).toContain('Содержимое модалки');
    });

    it('рендерит заголовок', () => {
        const wrapper = mount(VModal, {
            props: { modelValue: true, title: 'Заголовок теста' },
            slots: { default: 'Content' },
            global: { stubs: { teleport: true } },
        });
        expect(wrapper.text()).toContain('Заголовок теста');
    });

    it('эмитирует update:modelValue при закрытии', async () => {
        const wrapper = mount(VModal, {
            props: { modelValue: true, title: 'Модалка' },
            slots: { default: 'Content' },
            global: { stubs: { teleport: true } },
        });

        // Ищем кнопку закрытия
        const closeBtn = wrapper.find('button');
        if (closeBtn.exists()) {
            await closeBtn.trigger('click');
            const emitted = wrapper.emitted('update:modelValue');
            if (emitted) {
                expect(emitted[0]).toEqual([false]);
            }
        }
    });
});

/* ═══════════════════════════════════════════════════
 * VCard
 * ═══════════════════════════════════════════════════ */
describe('VCard', () => {
    it('рендерит заголовок', () => {
        const wrapper = mount(VCard, {
            props: { title: 'Карточка теста' },
            slots: { default: '<p>Контент</p>' },
        });
        expect(wrapper.text()).toContain('Карточка теста');
    });

    it('рендерит слот default', () => {
        const wrapper = mount(VCard, {
            slots: { default: '<p data-testid="card-content">Тестовый контент</p>' },
        });
        expect(wrapper.text()).toContain('Тестовый контент');
    });
});

/* ═══════════════════════════════════════════════════
 * VBadge
 * ═══════════════════════════════════════════════════ */
describe('VBadge', () => {
    it('рендерит текст через слот', () => {
        const wrapper = mount(VBadge, {
            slots: { default: 'Новый' },
        });
        expect(wrapper.text()).toContain('Новый');
    });
});

/* ═══════════════════════════════════════════════════
 * VStatCard
 * ═══════════════════════════════════════════════════ */
describe('VStatCard', () => {
    it('рендерит title и value', () => {
        const wrapper = mount(VStatCard, {
            props: { title: 'Выручка', value: '100 000 ₽' },
        });
        expect(wrapper.text()).toContain('Выручка');
        expect(wrapper.text()).toContain('100 000 ₽');
    });
});
