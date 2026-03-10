<?php

namespace App\Domains\Advertising\Interfaces;

/**
 * OrdDriverInterface - Контракт для драйверов ОРД (Объединенный реестр реклам).
 * 
 * Определяет минимальный интерфейс для взаимодействия с системой ОРД согласно 347-ФЗ.
 * Реализующие классы должны обрабатывать:
 * - Регистрацию договоров с рекламодателями
 * - Получение ERID (уникальный ID рекламы)
 * - Ежемесячную отчетность в ЕРИР
 * 
 * Все операции должны быть логированы и иметь аудит трейл.
 */
interface OrdDriverInterface
{
    /**
     * Регистрация договора в ОРД.
     * 
     * Создает новую запись договора в системе ОРД с данными рекламодателя,
     * сроками действия и условиями.
     *
     * @param array $data Данные договора:
     *   - advertiser_name (string): ФИО/название рекламодателя
     *   - advertiser_inn (string): ИНН рекламодателя
     *   - advertiser_address (string): Адрес рекламодателя
     *   - started_at (datetime): Дата начала действия договора
     *   - ended_at (datetime): Дата окончания договора
     *   - media_urls (array): Ссылки на рекламный материал
     * 
     * @return string OID (уникальный ID договора в ОРД)
     * 
     * @throws \Exception При ошибке регистрации договора
     * @throws \InvalidArgumentException При некорректных данных
     * 
     * @example
     *   $oid = $driver->createContract([
     *       'advertiser_name' => 'ООО "РеклаМотор"',
     *       'advertiser_inn' => '7799999999',
     *       'started_at' => now(),
     *       'ended_at' => now()->addMonth(),
     *   ]);
     */
    public function createContract(array $data): string;

    /**
     * Регистрация рекламного креатива и получение ERID.
     * 
     * Регистрирует рекламный материал в системе ОРД и получает ERID
     * (уникальный идентификатор рекламного объявления), который должен быть
     * отображен при показе согласно 347-ФЗ.
     *
     * @param array $data Данные креатива:
     *   - oid (string): OID договора (получен из createContract)
     *   - media_url (string): Ссылка на рекламный материал
     *   - creative_type (string): Тип контента (banner, video, native)
     *   - description (string, optional): Описание креатива
     *   - metadata (array, optional): Доп. метаданные
     * 
     * @return string ERID (уникальный ID рекламы в ОРД, для маркировки)
     * 
     * @throws \Exception При ошибке регистрации креатива
     * @throws \InvalidArgumentException При некорректных данных
     * @throws \RuntimeException При недоступности API ОРД
     * 
     * @example
     *   $erid = $driver->registerCreative([
     *       'oid' => $oid,
     *       'media_url' => 'https://example.com/banner.jpg',
     *       'creative_type' => 'banner',
     *   ]);
     */
    public function registerCreative(array $data): string;

    /**
     * Подача ежемесячной статистики в ЕРИР.
     * 
     * Отправляет данные о показах, кликах и других метриках в Единый реестр
     * информации о рекламе (ЕРИР). Должна вызываться один раз в месяц.
     *
     * @param array $stats Статистика:
     *   - oid (string): OID договора
     *   - period (string): Период отчета (YYYY-MM)
     *   - impressions (int): Количество показов
     *   - clicks (int): Количество кликов
     *   - conversions (int, optional): Количество конверсий
     *   - spend (decimal, optional): Потраченная сумма
     *   - metadata (array, optional): Доп. метаданные
     * 
     * @return void
     * 
     * @throws \Exception При ошибке отправки статистики
     * @throws \InvalidArgumentException При некорректных данных
     * 
     * @example
     *   $driver->pushStats([
     *       'oid' => $oid,
     *       'period' => '2026-03',
     *       'impressions' => 150000,
     *       'clicks' => 3500,
     *   ]);
     */
    public function pushStats(array $stats): void;
}
