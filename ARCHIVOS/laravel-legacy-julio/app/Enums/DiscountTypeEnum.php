<?php

namespace App\Enums;

enum DiscountTypeEnum: string
{
    case NONE = 'none';
    case AMOUNT = 'amount';
    case PERCENT = 'percent';

    public static function labels(): array
    {
        return [
            self::NONE->value => 'Sin Descuento',
            self::AMOUNT->value => 'Monto Fijo',
            self::PERCENT->value => 'Porcentaje',
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
        return self::from($value) ?? throw new \ValueError("Invalid discount type: {$value}");
    }
}
