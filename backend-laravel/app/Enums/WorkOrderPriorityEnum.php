<?php

namespace App\Enums;

enum WorkOrderPriorityEnum: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public static function labels(): array
    {
        return [
            self::LOW->value => 'Baja',
            self::MEDIUM->value => 'Media',
            self::HIGH->value => 'Alta',
            self::URGENT->value => 'Urgente',
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
        return self::from($value) ?? throw new \ValueError("Invalid work order priority: {$value}");
    }
}
