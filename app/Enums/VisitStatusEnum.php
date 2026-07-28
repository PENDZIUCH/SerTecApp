<?php

namespace App\Enums;

enum VisitStatusEnum: string
{
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public static function labels(): array
    {
        return [
            self::SCHEDULED->value => 'Programada',
            self::IN_PROGRESS->value => 'En Progreso',
            self::COMPLETED->value => 'Completada',
            self::CANCELLED->value => 'Cancelada',
            self::NO_SHOW->value => 'No se Presentó',
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
        return self::from($value) ?? throw new \ValueError("Invalid visit status: {$value}");
    }
}
