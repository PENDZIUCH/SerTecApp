<?php

namespace App\Enums;

enum MovementTypeEnum: string
{
    case IN = 'in';
    case OUT = 'out';
    case ADJUSTMENT = 'adjustment';
    case RETURN = 'return';

    public static function labels(): array
    {
        return [
            self::IN->value => 'Entrada',
            self::OUT->value => 'Salida',
            self::ADJUSTMENT->value => 'Ajuste',
            self::RETURN->value => 'Devolución',
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
        return self::from($value) ?? throw new \ValueError("Invalid movement type: {$value}");
    }
}
