<?php

namespace App\Enums;

enum SystemLogLevelEnum: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';

    public static function labels(): array
    {
        return [
            self::INFO->value => 'Información',
            self::WARNING->value => 'Advertencia',
            self::ERROR->value => 'Error',
            self::CRITICAL->value => 'Crítico',
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
        return self::from($value) ?? throw new \ValueError("Invalid log level: {$value}");
    }
}
