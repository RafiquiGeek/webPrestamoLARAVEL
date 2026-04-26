-- =====================================================
-- MIGRACIÓN COMPLETA: MEJORAS AL SISTEMA DE ESTADOS
-- Fecha: 2025-08-29
-- Descripción: Mejora la tabla pivot y campos para 
--              mejor control de estados y montos
-- =====================================================

-- 1. MEJORAR TABLA PIVOT OPERACIONES_CUOTA
-- =====================================================
ALTER TABLE `operaciones_cuota` 
ADD COLUMN `monto_aplicado` DECIMAL(10, 2) DEFAULT 0.00 
COMMENT 'Monto específico de la operación aplicado a esta cuota' AFTER `operacion_id`,

ADD COLUMN `concepto` VARCHAR(50) DEFAULT 'pago_general' 
COMMENT 'Concepto del pago: capital, interes, comision, igv, pago_general' AFTER `monto_aplicado`,

ADD COLUMN `observaciones` TEXT NULL 
COMMENT 'Observaciones específicas de la aplicación del pago a esta cuota' AFTER `concepto`,

ADD COLUMN `aplicado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
COMMENT 'Fecha y hora cuando se aplicó el pago a la cuota' AFTER `observaciones`;

-- Crear índices para optimizar consultas
CREATE INDEX `idx_operaciones_cuota_concepto` ON `operaciones_cuota` (`cuota_id`, `concepto`);
CREATE INDEX `idx_operaciones_cuota_monto` ON `operaciones_cuota` (`operacion_id`, `monto_aplicado`);

-- 2. CREAR/MEJORAR TABLA PIVOT OPERACION_MORA
-- =====================================================
CREATE TABLE IF NOT EXISTS `operacion_mora` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `operacion_id` BIGINT UNSIGNED NOT NULL,
    `mora_cuota_id` BIGINT UNSIGNED NOT NULL,
    `monto_aplicado` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Monto aplicado a esta mora específica',
    `observaciones` TEXT NULL COMMENT 'Observaciones del pago de mora',
    `aplicado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (`operacion_id`) REFERENCES `operaciones`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`mora_cuota_id`) REFERENCES `mora_cuota`(`id`) ON DELETE CASCADE,
    
    INDEX `idx_operacion_mora_operacion` (`operacion_id`),
    INDEX `idx_operacion_mora_mora` (`mora_cuota_id`),
    UNIQUE KEY `unique_operacion_mora` (`operacion_id`, `mora_cuota_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabla pivot para relacionar operaciones con moras específicas';

-- 3. POBLAR DATOS EXISTENTES
-- =====================================================

-- Actualizar monto_aplicado en operaciones_cuota con el abono de la operación
UPDATE `operaciones_cuota` oc
INNER JOIN `operaciones` o ON oc.operacion_id = o.id
SET oc.monto_aplicado = o.abono,
    oc.aplicado_en = COALESCE(o.created_at, NOW())
WHERE oc.monto_aplicado = 0.00 AND o.estado != 'anulado';

-- Actualizar concepto basado en tipo_operacion
UPDATE `operaciones_cuota` oc
INNER JOIN `operaciones` o ON oc.operacion_id = o.id
SET oc.concepto = CASE 
    WHEN o.tipo_operacion = 'Pago de cuota' THEN 'pago_cuota'
    WHEN o.tipo_operacion = 'Pago de mora' THEN 'pago_mora'
    WHEN o.tipo_operacion = 'Pago general' THEN 'pago_general'
    WHEN o.tipo_operacion = 'Desembolso' THEN 'desembolso'
    ELSE 'pago_general'
END
WHERE oc.concepto = 'pago_general';

-- 4. ACTUALIZAR CAMPOS MONTO_PAGADO
-- =====================================================

-- Recalcular monto_pagado en cuotas desde operaciones activas
UPDATE `cuotas` c
SET c.monto_pagado = (
    SELECT COALESCE(SUM(o.abono), 0)
    FROM `operaciones_cuota` oc
    INNER JOIN `operaciones` o ON oc.operacion_id = o.id
    WHERE oc.cuota_id = c.id 
    AND o.estado != 'anulado'
)
WHERE c.id IN (
    SELECT DISTINCT cuota_id 
    FROM `operaciones_cuota`
);

-- Recalcular monto_pagado en moras desde operaciones activas
UPDATE `mora_cuota` mc
SET mc.monto_pagado = (
    SELECT COALESCE(SUM(o.abono), 0)
    FROM `operacion_mora` om
    INNER JOIN `operaciones` o ON om.operacion_id = o.id
    WHERE om.mora_cuota_id = mc.id 
    AND o.estado != 'anulado'
)
WHERE mc.monto_pagado IS NULL OR mc.monto_pagado = 0;

-- 5. ACTUALIZAR ESTADOS DE CUOTAS
-- =====================================================

-- Actualizar cuotas completamente pagadas
UPDATE `cuotas` c
SET c.estado = 2 -- PAGADO
WHERE c.monto_pagado >= c.monto 
AND c.estado != 2;

-- Actualizar cuotas con pagos parciales
UPDATE `cuotas` c
SET c.estado = 1 -- PARCIAL
WHERE c.monto_pagado > 0 
AND c.monto_pagado < c.monto 
AND c.estado = 0; -- Solo si estaba PENDIENTE

-- Actualizar cuotas vencidas (pendientes o parciales con fecha pasada)
UPDATE `cuotas` c
SET c.estado = 3 -- VENCIDO
WHERE c.fecha_pago < CURDATE()
AND c.estado IN (0, 1) -- PENDIENTE o PARCIAL
AND c.monto_pagado < c.monto;

-- 6. ACTUALIZAR ESTADOS DE MORAS
-- =====================================================

-- Actualizar moras completamente pagadas
UPDATE `mora_cuota` mc
SET mc.estado = 2 -- PAGADO (MoraCuotaEstado::PAGADO)
WHERE mc.monto_pagado >= mc.monto 
AND mc.estado != 2;

-- Actualizar moras con pagos parciales
UPDATE `mora_cuota` mc
SET mc.estado = 1 -- PARCIAL (MoraCuotaEstado::PARCIAL)
WHERE mc.monto_pagado > 0 
AND mc.monto_pagado < mc.monto 
AND mc.estado = 0; -- Solo si estaba PENDIENTE

-- 7. ACTUALIZAR ESTADOS DE PRÉSTAMOS
-- =====================================================

-- Marcar préstamos como finalizados (todas las cuotas pagadas)
UPDATE `prestamos` p
SET p.estado = 'Finalizado'
WHERE p.estado != 'Finalizado'
AND NOT EXISTS (
    SELECT 1 FROM `cuotas` c 
    WHERE c.prestamo_id = p.id 
    AND c.estado != 2 -- No pagado
);

-- Marcar préstamos como morosos (tienen moras pendientes o cuotas vencidas)
UPDATE `prestamos` p
SET p.estado = 'Moroso'
WHERE p.estado NOT IN ('Finalizado', 'Moroso')
AND (
    -- Tiene moras pendientes o parciales
    EXISTS (
        SELECT 1 FROM `cuotas` c
        INNER JOIN `mora_cuota` mc ON c.id = mc.cuota_id
        WHERE c.prestamo_id = p.id 
        AND mc.estado IN (0, 1) -- PENDIENTE o PARCIAL
    )
    OR
    -- Tiene cuotas vencidas
    EXISTS (
        SELECT 1 FROM `cuotas` c
        WHERE c.prestamo_id = p.id 
        AND c.estado = 3 -- VENCIDO
    )
);

-- Marcar préstamos como vigentes (tienen pagos pero no están en mora)
UPDATE `prestamos` p
SET p.estado = 'Vigente'
WHERE p.estado NOT IN ('Finalizado', 'Moroso', 'Vigente')
AND EXISTS (
    SELECT 1 FROM `cuotas` c 
    WHERE c.prestamo_id = p.id 
    AND c.estado IN (1, 2) -- PARCIAL o PAGADO
)
AND NOT EXISTS (
    SELECT 1 FROM `cuotas` c
    WHERE c.prestamo_id = p.id 
    AND c.estado = 3 -- VENCIDO
);

-- 8. VERIFICACIÓN DE RESULTADOS
-- =====================================================

-- Mostrar resumen de la migración
SELECT 
    'Operaciones-Cuota con monto aplicado' as concepto,
    COUNT(*) as cantidad
FROM `operaciones_cuota` 
WHERE monto_aplicado > 0

UNION ALL

SELECT 
    'Cuotas con monto pagado' as concepto,
    COUNT(*) as cantidad
FROM `cuotas` 
WHERE monto_pagado > 0

UNION ALL

SELECT 
    'Moras con monto pagado' as concepto,
    COUNT(*) as cantidad
FROM `mora_cuota` 
WHERE monto_pagado > 0

UNION ALL

SELECT 
    'Préstamos Finalizados' as concepto,
    COUNT(*) as cantidad
FROM `prestamos` 
WHERE estado = 'Finalizado'

UNION ALL

SELECT 
    'Préstamos Morosos' as concepto,
    COUNT(*) as cantidad
FROM `prestamos` 
WHERE estado = 'Moroso'

UNION ALL

SELECT 
    'Préstamos Vigentes' as concepto,
    COUNT(*) as cantidad
FROM `prestamos` 
WHERE estado = 'Vigente';

-- =====================================================
-- NOTAS IMPORTANTES:
-- =====================================================
/*
1. Esta migración debe ejecutarse en una transacción si es posible
2. Hacer backup antes de ejecutar
3. Después de la migración, ejecutar los comandos:
   - php artisan sistema:verificar-integridad
   - php artisan sistema:reparar-estados --dry-run
4. Los nuevos controladores mejorados deben reemplazar a los originales
5. El EstadoPrestamoService debe estar registrado en AppServiceProvider
*/

-- FIN DE MIGRACIÓN