<?php

namespace App\Domains\Communication\Enums;

enum MessageStatus: string
{
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case READ = 'read';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';

    public function label(): string
    {
        return match($this) {
            self::SENT => 'Отправлено',
            self::DELIVERED => 'Доставлено',
            self::READ => 'Прочитано',
            self::ARCHIVED => 'В архиве',
            self::DELETED => 'Удалено',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::SENT => 'check',
            self::DELIVERED => 'check-all',
            self::READ => 'check-all',
            self::ARCHIVED => 'archive',
            self::DELETED => 'trash',
        };
    }

    public function canBeDeleted(): bool
    {
        return $this !== self::DELETED;
    }
}
