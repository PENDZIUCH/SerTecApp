<?php

namespace App\Enums;

enum PdfTemplateKeyEnum: string
{
    case WORK_ORDER = 'work_order';
    case BUDGET = 'budget';
    case INVOICE = 'invoice';
    case SERVICE_REPORT = 'service_report';
    case WARRANTY = 'warranty';
    case CONTRACT = 'contract';

    public static function labels(): array
    {
        return [
            self::WORK_ORDER->value => 'Orden de Trabajo',
            self::BUDGET->value => 'Presupuesto',
            self::INVOICE->value => 'Factura',
            self::SERVICE_REPORT->value => 'Reporte de Servicio',
            self::WARRANTY->value => 'Garantía',
            self::CONTRACT->value => 'Contrato',
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
        return self::from($value) ?? throw new \ValueError("Invalid PDF template key: {$value}");
    }
}
