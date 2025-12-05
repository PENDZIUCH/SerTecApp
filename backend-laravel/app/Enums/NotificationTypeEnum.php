<?php

namespace App\Enums;

enum NotificationTypeEnum: string
{
    case WORK_ORDER_ASSIGNED = 'work_order_assigned';
    case WORK_ORDER_COMPLETED = 'work_order_completed';
    case VISIT_SCHEDULED = 'visit_scheduled';
    case VISIT_REMINDER = 'visit_reminder';
    case EQUIPMENT_SERVICE_DUE = 'equipment_service_due';
    case SUBSCRIPTION_EXPIRING = 'subscription_expiring';
    case BUDGET_APPROVED = 'budget_approved';
    case BUDGET_REJECTED = 'budget_rejected';
    case LOW_STOCK = 'low_stock';
    case SYSTEM_ALERT = 'system_alert';

    public static function labels(): array
    {
        return [
            self::WORK_ORDER_ASSIGNED->value => 'Orden de Trabajo Asignada',
            self::WORK_ORDER_COMPLETED->value => 'Orden de Trabajo Completada',
            self::VISIT_SCHEDULED->value => 'Visita Programada',
            self::VISIT_REMINDER->value => 'Recordatorio de Visita',
            self::EQUIPMENT_SERVICE_DUE->value => 'Servicio de Equipo Vencido',
            self::SUBSCRIPTION_EXPIRING->value => 'Suscripción por Vencer',
            self::BUDGET_APPROVED->value => 'Presupuesto Aprobado',
            self::BUDGET_REJECTED->value => 'Presupuesto Rechazado',
            self::LOW_STOCK->value => 'Stock Bajo',
            self::SYSTEM_ALERT->value => 'Alerta del Sistema',
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
        return self::from($value) ?? throw new \ValueError("Invalid notification type: {$value}");
    }
}
