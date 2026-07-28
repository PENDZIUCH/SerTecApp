<?php

namespace App\Enums;

enum WorkOrderStatusEnum: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public static function labels(): array
    {
        return [
            self::PENDING->value => 'Pendiente',
            self::IN_PROGRESS->value => 'En Progreso',
            self::COMPLETED->value => 'Completada',
            self::CANCELLED->value => 'Cancelada',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromOrFail(string $value): self
    {
        return self::from($value) ?? throw new \ValueError("Invalid work order status: {$value}");
    }
}
