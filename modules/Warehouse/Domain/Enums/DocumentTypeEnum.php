<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Enums;

/**
 * Document Type Enum
 * 
 * Типы документов в системе документооборота склада
 */
enum DocumentTypeEnum: string
{
    case RECEIPT_ACT = 'receipt_act';           // Акт приемки
    case SHIPMENT_ACT = 'shipment_act';         // Акт отгрузки
    case TRANSFER_ACT = 'transfer_act';         // Акт перемещения
    case WRITE_OFF_ACT = 'write_off_act';       // Акт списания
    case INVENTORY_ACT = 'inventory_act';       // Акт инвентаризации
    case RETURN_ACT = 'return_act';             // Акт возврата
    case DAMAGE_ACT = 'damage_act';             // Акт порчи
    case LOSS_ACT = 'loss_act';                 // Акт недостачи
    case QUARANTINE_ACT = 'quarantine_act';     // Акт карантина
    case EXPIRATION_ACT = 'expiration_act';     // Акт истечения срока
    case ADJUSTMENT_ACT = 'adjustment_act';     // Акт корректировки
    case SUPPLIER_INVOICE = 'supplier_invoice'; // Счет поставщика
    case CUSTOMS_DECLARATION = 'customs_declaration'; // Таможенная декларация
    case CERTIFICATE = 'certificate';           // Сертификат
}
