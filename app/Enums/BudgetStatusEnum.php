<?php

namespace App\Enums;

enum BudgetStatusEnum: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public static function labels(): array
    {
        return [
            self::DRAFT->value => 'Borrador',
            self::SENT->value => 'Enviado',
            self::APPROVED->value => 'Aprobado',
            self::REJECTED->value => 'Rechazado',
            self::CANCELLED->value => 'Cancelado',
            self::EXPIRED->value => 'Vencido',
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
        return self::from($value) ?? throw new \ValueError("Invalid budget status: {$value}");
    }
}
