-- Insertar 3 órdenes de trabajo para el técnico ID 1
INSERT INTO work_orders (customer_id, equipment_id, assigned_technician_id, description, priority, status, notes, created_at, updated_at)
SELECT 
    id as customer_id,
    NULL as equipment_id,
    1 as assigned_technician_id,
    CASE 
        WHEN ROW_NUMBER() OVER (ORDER BY id) = 1 THEN 'Cinta no enciende - revisar conexión eléctrica'
        WHEN ROW_NUMBER() OVER (ORDER BY id) = 2 THEN 'Bici hace ruido en pedal derecho - ajustar rodamiento'
        ELSE 'Remo pierde resistencia - verificar sistema hidráulico'
    END as description,
    CASE 
        WHEN ROW_NUMBER() OVER (ORDER BY id) = 1 THEN 4
        WHEN ROW_NUMBER() OVER (ORDER BY id) = 2 THEN 2
        ELSE 3
    END as priority,
    'pending' as status,
    'Orden de trabajo sin equipo asignado' as notes,
    NOW() as created_at,
    NOW() as updated_at
FROM customers
LIMIT 3;
