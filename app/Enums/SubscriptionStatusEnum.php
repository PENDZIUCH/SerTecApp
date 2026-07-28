<?php

namespace App\Enums;

enum SubscriptionStatusEnum: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public static function labels(): array
    {
        return [
            self::ACTIVE->value => 'Activa',
            self::SUSPENDED->value => 'Suspendida',
            self::CANCELLED->value => 'Cancelada',
            self::EXPIRED->value => 'Vencida',
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
        return self::from($value) ?? throw new \ValueError("Invalid subscription status: {$value}");
    }
}
