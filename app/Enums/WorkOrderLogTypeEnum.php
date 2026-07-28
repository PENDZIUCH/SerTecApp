<?php

namespace App\Enums;

enum WorkOrderLogTypeEnum: string
{
    case STATUS_CHANGE = 'status_change';
    case ASSIGNMENT = 'assignment';
    case COMMENT = 'comment';
    case PART_ADDED = 'part_added';
    case COST_UPDATE = 'cost_update';

    public static function labels(): array
    {
        return [
            self::STATUS_CHANGE->value => 'Cambio de Estado',
            self::ASSIGNMENT->value => 'Asignación',
            self::COMMENT->value => 'Comentario',
            self::PART_ADDED->value => 'Repuesto Agregado',
            self::COST_UPDATE->value => 'Actualización de Costo',
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
        return self::from($value) ?? throw new \ValueError("Invalid log type: {$value}");
    }
}
