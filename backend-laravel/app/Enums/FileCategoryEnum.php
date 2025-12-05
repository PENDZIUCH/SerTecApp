<?php

namespace App\Enums;

enum FileCategoryEnum: string
{
    case CONTRACT = 'contract';
    case INVOICE = 'invoice';
    case WARRANTY = 'warranty';
    case MANUAL = 'manual';
    case PHOTO = 'photo';
    case REPORT = 'report';
    case OTHER = 'other';

    public static function labels(): array
    {
        return [
            self::CONTRACT->value => 'Contrato',
            self::INVOICE->value => 'Factura',
            self::WARRANTY->value => 'Garantía',
            self::MANUAL->value => 'Manual',
            self::PHOTO->value => 'Foto',
            self::REPORT->value => 'Reporte',
            self::OTHER->value => 'Otro',
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
        return self::from($value) ?? throw new \ValueError("Invalid file category: {$value}");
    }
}
