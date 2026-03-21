/**
 * Analytics Data Formatter Utility
 * Форматирование данных аналитики для отображения
 * 
 * @module resources/js/utils/analyticsFormatter
 * @requires none
 */

'use strict';

/**
 * Форматирует число как валюту с символом рубля
 * @param {number} value - значение
 * @param {number} decimals - количество знаков после запятой (по умолчанию 0)
 * @returns {string} - форматированная строка (например, "₽ 125 000")
 */
export const formatCurrency = (value, decimals = 0) => {
    if (typeof value !== 'number') return '₽ 0';
    return '₽ ' + new Intl.NumberFormat('ru-RU', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(value);
};

/**
 * Форматирует число как процент с символом %
 * @param {number} value - значение (0-100 или 0-1)
 * @param {boolean} isDecimal - если true, то value в диапазоне 0-1
 * @returns {string} - форматированная строка (например, "25.5%")
 */
export const formatPercent = (value, isDecimal = false) => {
    if (typeof value !== 'number') return '0%';
    const percent = isDecimal ? value * 100 : value;
    return percent.toFixed(1) + '%';
};

/**
 * Форматирует число с тысячами (например, "1 250")
 * @param {number} value - значение
 * @returns {string} - форматированная строка
 */
export const formatNumber = (value) => {
    if (typeof value !== 'number') return '0';
    return new Intl.NumberFormat('ru-RU').format(value);
};

/**
 * Форматирует дату в формат "d.m.Y H:i"
 * @param {string|Date} date - дата
 * @returns {string} - форматированная дата (например, "15.03.2026 14:30")
 */
export const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}.${month}.${year} ${hours}:${minutes}`;
};

/**
 * Вычисляет тренд (стрелка + процент) для сравнения периодов
 * @param {number} current - текущее значение
 * @param {number} previous - предыдущее значение
 * @returns {object} - объект {icon: string, text: string, isPositive: boolean, percent: number}
 */
export const calculateTrend = (current, previous) => {
    if (!previous || previous === 0) return {
        icon: '→',
        text: 'нет данных',
        isPositive: null,
        percent: 0
    };

    const changePercent = ((current - previous) / Math.abs(previous)) * 100;
    const isPositive = changePercent > 0;

    return {
        icon: isPositive ? '↑' : '↓',
        text: formatPercent(Math.abs(changePercent), false),
        isPositive,
        percent: changePercent
    };
};

/**
 * Генерирует CSS-класс для color-coding на основе value
 * Используется для KPI-карточек (зелёный, жёлтый, красный)
 * @param {number} value - значение
 * @param {number} warningThreshold - порог для жёлтого (по умолчанию 50)
 * @param {number} dangerThreshold - порог для красного (по умолчанию 30)
 * @returns {string} - CSS-класс ("success", "warning", "danger")
 */
export const getColorClass = (value, warningThreshold = 50, dangerThreshold = 30) => {
    if (value >= warningThreshold) return 'success';
    if (value >= dangerThreshold) return 'warning';
    return 'danger';
};

/**
 * Сокращает большие числа до формата K/M (например, "125K", "1.2M")
 * Используется для компактного отображения в микровизуализациях
 * @param {number} value - значение
 * @returns {string} - сокращённая строка
 */
export const formatCompact = (value) => {
    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
    if (value >= 1000) return (value / 1000).toFixed(1) + 'K';
    return String(value);
};

/**
 * Преобразует строку в title case (Первая Буква Каждого Слова)
 * @param {string} str - строка
 * @returns {string} - преобразованная строка
 */
export const titleCase = (str) => {
    if (!str) return '';
    return str.split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
};

/**
 * Экспортирует все функции для использования в компонентах
 */
export default {
    formatCurrency,
    formatPercent,
    formatNumber,
    formatDate,
    calculateTrend,
    getColorClass,
    formatCompact,
    titleCase
};
