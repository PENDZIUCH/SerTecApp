<?php

namespace App\Enums;

enum BillingCycleEnum: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';

    public static function labels(): array
    {
        return [
            self::MONTHLY->value => 'Mensual',
            self::QUARTERLY->value => 'Trimestral',
            self::YEARLY->value => 'Anual',
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
        return self::from($value) ?? throw new \ValueError("Invalid billing cycle: {$value}");
    }
}
