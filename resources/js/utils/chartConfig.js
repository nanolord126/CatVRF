/**
 * Chart Configuration Utility
 * Конфигурация графиков Chart.js для аналитики
 * 
 * @module resources/js/utils/chartConfig
 * @requires chart.js
 */

'use strict';

/**
 * Базовые цвета палитры платформы
 */
const COLORS = {
    primary: '#2563eb',      // Синий
    success: '#10b981',      // Зелёный
    warning: '#f59e0b',      // Жёлтый/Оранжевый
    danger: '#ef4444',       // Красный
    info: '#06b6d4',         // Голубой
    secondary: '#8b5cf6',    // Фиолетовый
    neutral: '#6b7280'       // Серый
};

/**
 * Генерирует конфигурацию линейного графика (Line Chart)
 * Используется для показа трендов (выручка, заказы и т.д.)
 * 
 * @param {string[]} labels - метки оси X (например, дни недели)
 * @param {number[]} data - данные для графика
 * @param {object} options - доп. опции
 * @returns {object} - конфигурация Chart.js
 */
export const getLineChartConfig = (labels, data, options = {}) => {
    const {
        label = 'Тренд',
        color = COLORS.primary,
        tension = 0.4,
        fill = true,
        backgroundColor = 'rgba(37, 99, 235, 0.1)'
    } = options;

    return {
        labels: labels,
        datasets: [
            {
                label: label,
                data: data,
                borderColor: color,
                backgroundColor: backgroundColor,
                borderWidth: 2,
                tension: tension,
                fill: fill,
                pointRadius: 5,
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 7
            }
        ]
    };
};

/**
 * Генерирует конфигурацию столбчатой диаграммы (Bar Chart)
 * Используется для сравнения метрик или показа воронки конверсии
 * 
 * @param {string[]} labels - метки оси X
 * @param {number[]|object[]} datasets - один или несколько массивов данных
 * @param {object} options - доп. опции
 * @returns {object} - конфигурация Chart.js
 */
export const getBarChartConfig = (labels, datasets, options = {}) => {
    const {
        colors = [COLORS.primary, COLORS.secondary, COLORS.info, COLORS.success]
    } = options;

    // Если передан простой массив, преобразуем его
    if (Array.isArray(datasets[0]) && typeof datasets[0][0] === 'number') {
        datasets = [{
            label: 'Значения',
            data: datasets[0],
            backgroundColor: colors[0]
        }];
    }

    // Если передан массив объектов, обогащаем цветами
    if (Array.isArray(datasets) && datasets.length > 0 && !datasets[0].data) {
        // datasets уже в нормальном формате
    } else {
        datasets = datasets.map((data, index) => ({
            label: `Метрика ${index + 1}`,
            data: data,
            backgroundColor: colors[index % colors.length]
        }));
    }

    return {
        labels: labels,
        datasets: datasets
    };
};

/**
 * Генерирует конфигурацию круговой диаграммы (Pie/Doughnut Chart)
 * Используется для показа распределения (сегменты, категории и т.д.)
 * 
 * @param {string[]} labels - метки (названия сегментов)
 * @param {number[]} data - значения
 * @param {object} options - доп. опции
 * @returns {object} - конфигурация Chart.js
 */
export const getPieChartConfig = (labels, data, options = {}) => {
    const {
        colors = [COLORS.success, COLORS.warning, COLORS.danger, COLORS.primary],
        isDoughnut = true
    } = options;

    return {
        labels: labels,
        datasets: [
            {
                data: data,
                backgroundColor: colors.slice(0, data.length),
                borderColor: '#fff',
                borderWidth: 2
            }
        ]
    };
};

/**
 * Генерирует конфигурацию radar-графика (Radar Chart)
 * Используется для сравнения метрик по нескольким осям
 * 
 * @param {string[]} labels - оси (названия метрик)
 * @param {number[]} data - значения
 * @param {object} options - доп. опции
 * @returns {object} - конфигурация Chart.js
 */
export const getRadarChartConfig = (labels, data, options = {}) => {
    const {
        label = 'Метрики',
        color = COLORS.primary
    } = options;

    return {
        labels: labels,
        datasets: [
            {
                label: label,
                data: data,
                borderColor: color,
                backgroundColor: `rgba(37, 99, 235, 0.2)`,
                pointBackgroundColor: color,
                borderWidth: 2,
                pointRadius: 5
            }
        ]
    };
};

/**
 * Генерирует базовые опции для всех типов графиков
 * Применяется к Context Vue-компонента или Filament widget
 * 
 * @param {object} customOptions - пользовательские опции для переопределения
 * @returns {object} - опции Chart.js
 */
export const getDefaultChartOptions = (customOptions = {}) => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                labels: {
                    font: { size: 12, family: 'Segoe UI, Roboto, sans-serif' },
                    usePointStyle: true,
                    padding: 15,
                    color: '#6b7280'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: { size: 13 },
                bodyFont: { size: 12 },
                padding: 12,
                cornerRadius: 6,
                displayColors: true
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: { size: 11 },
                    color: '#9ca3af'
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                ticks: {
                    font: { size: 11 },
                    color: '#9ca3af'
                },
                grid: {
                    display: false
                }
            }
        },
        ...customOptions
    };
};

/**
 * Генерирует конфигурацию Area Chart (как Line, но с заливкой)
 * @param {string[]} labels
 * @param {number[]} data
 * @param {object} options
 * @returns {object}
 */
export const getAreaChartConfig = (labels, data, options = {}) => {
    const {
        label = 'Тренд',
        color = COLORS.primary
    } = options;

    return {
        labels: labels,
        datasets: [
            {
                label: label,
                data: data,
                borderColor: color,
                backgroundColor: `rgba(37, 99, 235, 0.2)`,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 7
            }
        ]
    };
};

/**
 * Генерирует конфигурацию горизонтальной bar-диаграммы
 * Используется для рангирования (топ товары, топ категории и т.д.)
 * 
 * @param {string[]} labels
 * @param {number[]} data
 * @param {object} options
 * @returns {object}
 */
export const getHorizontalBarChartConfig = (labels, data, options = {}) => {
    const {
        color = COLORS.primary
    } = options;

    return {
        labels: labels,
        datasets: [
            {
                label: 'Значение',
                data: data,
                backgroundColor: color,
                borderRadius: 4
            }
        ]
    };
};

/**
 * Комбинированная конфигурация (Line + Bar на одном графике)
 * @param {string[]} labels
 * @param {number[]} lineData
 * @param {number[]} barData
 * @param {object} options
 * @returns {object}
 */
export const getCombinedChartConfig = (labels, lineData, barData, options = {}) => {
    const {
        lineLabel = 'Тренд',
        barLabel = 'Сравнение'
    } = options;

    return {
        labels: labels,
        datasets: [
            {
                type: 'line',
                label: lineLabel,
                data: lineData,
                borderColor: COLORS.primary,
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                yAxisID: 'y'
            },
            {
                type: 'bar',
                label: barLabel,
                data: barData,
                backgroundColor: 'rgba(16, 185, 129, 0.3)',
                borderColor: COLORS.success,
                borderWidth: 1,
                yAxisID: 'y1'
            }
        ]
    };
};

/**
 * Экспортирует все функции
 */
export default {
    COLORS,
    getLineChartConfig,
    getBarChartConfig,
    getPieChartConfig,
    getRadarChartConfig,
    getDefaultChartOptions,
    getAreaChartConfig,
    getHorizontalBarChartConfig,
    getCombinedChartConfig
};
