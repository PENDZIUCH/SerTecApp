<?php

namespace App\Enums;

enum EquipmentStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case IN_WORKSHOP = 'in_workshop';
    case DECOMMISSIONED = 'decommissioned';

    public static function labels(): array
    {
        return [
            self::ACTIVE->value => 'Activo',
            self::INACTIVE->value => 'Inactivo',
            self::IN_WORKSHOP->value => 'En Taller',
            self::DECOMMISSIONED->value => 'Dado de Baja',
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
        return self::from($value) ?? throw new \ValueError("Invalid equipment status: {$value}");
    }
}
