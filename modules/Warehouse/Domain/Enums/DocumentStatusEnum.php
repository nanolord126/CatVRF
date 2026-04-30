<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Enums;

/**
 * Document Status Enum
 * 
 * Статусы документов в системе документооборота
 */
enum DocumentStatusEnum: string
{
    case DRAFT = 'draft';                   // Черновик
    case PENDING_APPROVAL = 'pending_approval'; // На согласовании
    case APPROVED = 'approved';             // Согласован
    case REJECTED = 'rejected';             // Отклонен
    case ARCHIVED = 'archived';             // Архивирован
    case CANCELLED = 'cancelled';           // Отменен
    case PROCESSING = 'processing';         // В обработке
    case COMPLETED = 'completed';           // Выполнен
}
