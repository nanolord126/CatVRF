/**
 * Цветовая схема для B2B и B2C
 * B2C на 17 тонов ярче B2B
 */

export const B2B_COLOR = '#3B82F6'; // Blue-500
export const B2C_COLOR = '#60A5FA'; // Blue-400 (на 17 тонов ярче)

export const B2B_COLORS = {
  primary: '#3B82F6',
  light: '#93C5FD',
  dark: '#1E40AF',
  background: '#EFF6FF',
  border: '#DBEAFE',
  text: '#1E3A8A',
};

export const B2C_COLORS = {
  primary: '#60A5FA',
  light: '#BFDBFE',
  dark: '#2563EB',
  background: '#DBEAFE',
  border: '#BFDBFE',
  text: '#1E40AF',
};

export const ORDER_TYPE_COLORS = {
  b2b: B2B_COLORS,
  b2c: B2C_COLORS,
};

export function getOrderTypeColor(type: 'b2b' | 'b2c', shade: 'primary' | 'light' | 'dark' | 'background' | 'border' | 'text' = 'primary'): string {
  return ORDER_TYPE_COLORS[type][shade];
}

export function isOrderTypeB2C(type: string): boolean {
  return type === 'b2c';
}
