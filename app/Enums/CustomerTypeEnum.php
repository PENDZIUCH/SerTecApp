<?php

namespace App\Enums;

enum CustomerTypeEnum: string
{
    case INDIVIDUAL = 'individual';
    case COMPANY = 'company';
    case GYM = 'gym';

    public static function labels(): array
    {
        return [
            self::INDIVIDUAL->value => 'Individual',
            self::COMPANY->value => 'Empresa',
            self::GYM->value => 'Gimnasio',
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
        return self::from($value) ?? throw new \ValueError("Invalid customer type: {$value}");
    }
}
