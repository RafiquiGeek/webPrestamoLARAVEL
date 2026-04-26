-- =====================================================
-- OPTIMIZACIÓN DE ÍNDICES PARA MÓDULO DE DEUDAS
-- Fecha: 2025-12-22
-- Descripción: Crea índices compuestos para mejorar el
--              rendimiento de consultas de deudas
-- =====================================================

-- Crear procedimiento para crear índices con manejo de errores
DELIMITER $$

DROP PROCEDURE IF EXISTS create_index_safe$$

CREATE PROCEDURE create_index_safe(
    p_table_name VARCHAR(64),
    p_index_name VARCHAR(64),
    p_column_list VARCHAR(255),
    p_index_type VARCHAR(20)
)
BEGIN
    DECLARE index_exists INT DEFAULT 0;

    -- Verificar si el índice ya existe
    SELECT COUNT(*) INTO index_exists
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = p_table_name
      AND index_name = p_index_name;

    IF index_exists > 0 THEN
        SELECT CONCAT('⚠️  Índice ya existe: ', p_index_name, ' en tabla ', p_table_name) AS 'Estado';
    ELSE
        -- Construir y ejecutar el comando CREATE INDEX
        SET @sql = CONCAT('CREATE ',
            IF(p_index_type = 'FULLTEXT', 'FULLTEXT ', ''),
            'INDEX ', p_index_name, ' ON ',
            p_table_name, ' (', p_column_list, ')');

        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        SELECT CONCAT('✅ Índice creado: ', p_index_name, ' en tabla ', p_table_name) AS 'Estado';
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- CREAR ÍNDICES COMPUESTOS
-- =====================================================

SELECT '🚀 Iniciando creación de índices para optimización de deudas...' AS 'Estado';

-- 1. ÍNDICES EN TABLA CUOTAS
SELECT '📊 Creando índices en tabla CUOTAS...' AS 'Estado';
CALL create_index_safe('cuotas', 'idx_cuotas_prestamo_estado_fecha', 'prestamo_id, estado, fecha_pago', 'INDEX');
CALL create_index_safe('cuotas', 'idx_cuotas_fecha_estado', 'fecha_pago, estado', 'INDEX');

-- 2. ÍNDICES EN TABLA MORA_CUOTA
SELECT '📊 Creando índices en tabla MORA_CUOTA...' AS 'Estado';
CALL create_index_safe('mora_cuota', 'idx_mora_cuota_estado', 'cuota_id, estado', 'INDEX');
CALL create_index_safe('mora_cuota', 'idx_mora_estado_dias', 'estado, dias_mora', 'INDEX');

-- 3. ÍNDICES EN TABLA CARTERA_JCC
SELECT '📊 Creando índices en tabla CARTERA_JCC...' AS 'Estado';
CALL create_index_safe('cartera_jcc', 'idx_cartera_jcc_prestamo_estado', 'prestamo_id, estado, jcc_id', 'INDEX');
CALL create_index_safe('cartera_jcc', 'idx_cartera_jcc_lookup', 'jcc_id, estado', 'INDEX');

-- 4. ÍNDICES EN TABLA CARTERA_ASESOR
SELECT '📊 Creando índices en tabla CARTERA_ASESOR...' AS 'Estado';
CALL create_index_safe('cartera_asesor', 'idx_cartera_asesor_prestamo_estado', 'prestamo_id, estado, asesor_id', 'INDEX');
CALL create_index_safe('cartera_asesor', 'idx_cartera_asesor_lookup', 'asesor_id, estado', 'INDEX');

-- 5. ÍNDICES EN TABLA CARTERA_ANALISTA
SELECT '📊 Creando índices en tabla CARTERA_ANALISTA...' AS 'Estado';
CALL create_index_safe('cartera_analista', 'idx_cartera_analista_prestamo_estado', 'prestamo_id, estado, analista_id', 'INDEX');
CALL create_index_safe('cartera_analista', 'idx_cartera_analista_lookup', 'analista_id, estado', 'INDEX');

-- 6. ÍNDICES EN TABLA DIRECCIONES
SELECT '📊 Creando índices en tabla DIRECCIONES...' AS 'Estado';
CALL create_index_safe('direcciones', 'idx_direcciones_persona_sucursal', 'persona_id, sucursal_id', 'INDEX');

-- 7. ÍNDICES EN TABLA SUCURSAL_ZONA (si existe)
SELECT '📊 Creando índices en tabla SUCURSAL_ZONA...' AS 'Estado';
-- Verificar si la tabla existe antes de crear el índice
SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
    AND table_name = 'sucursal_zona'
);

SET @create_index = IF(@table_exists > 0,
    "CALL create_index_safe('sucursal_zona', 'idx_sucursal_zona_lookup', 'sucursal_id, zona_id', 'INDEX')",
    "SELECT '⚠️  Tabla sucursal_zona no existe, omitiendo...' AS 'Estado'"
);

PREPARE stmt FROM @create_index;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. ÍNDICES EN TABLA GESTIONES
SELECT '📊 Creando índices en tabla GESTIONES...' AS 'Estado';
CALL create_index_safe('gestiones', 'idx_gestiones_prestamo_fecha', 'prestamo_id, fecha', 'INDEX');

-- 9. ÍNDICES EN TABLA COMPROMISOS
SELECT '📊 Creando índices en tabla COMPROMISOS...' AS 'Estado';
CALL create_index_safe('compromisos', 'idx_compromisos_prestamo_estado_fecha', 'prestamo_id, estado, fecha_compromiso_pago', 'INDEX');

-- 10. ÍNDICES EN TABLA CONVENIOS
SELECT '📊 Creando índices en tabla CONVENIOS...' AS 'Estado';
CALL create_index_safe('convenios', 'idx_convenios_prestamo_estado', 'prestamo_id, estado', 'INDEX');

-- 11. ÍNDICES EN TABLA CUOTA_CONVENIO_MODELS
SELECT '📊 Creando índices en tabla CUOTA_CONVENIO_MODELS...' AS 'Estado';
CALL create_index_safe('cuota_convenio_models', 'idx_cuota_convenio_lookup', 'convenio_id, estado, fecha_vencimiento', 'INDEX');

-- 12. ÍNDICE FULLTEXT EN TABLA PERSONAS (para búsquedas rápidas)
SELECT '📊 Creando índice FULLTEXT en tabla PERSONAS...' AS 'Estado';

-- Verificar si ya existe un índice FULLTEXT
SET @fulltext_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'personas'
    AND index_type = 'FULLTEXT'
);

SET @create_fulltext = IF(@fulltext_exists = 0,
    "CALL create_index_safe('personas', 'idx_personas_search', 'nombres, ape_pat, ape_mat', 'FULLTEXT')",
    "SELECT '⚠️  Índice FULLTEXT ya existe en tabla personas' AS 'Estado'"
);

PREPARE stmt FROM @create_fulltext;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- VERIFICAR ÍNDICES CREADOS
-- =====================================================

SELECT '📋 Verificando índices creados...' AS 'Estado';

SELECT
    table_name AS 'Tabla',
    index_name AS 'Índice',
    GROUP_CONCAT(column_name ORDER BY seq_in_index) AS 'Columnas',
    index_type AS 'Tipo'
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND (
    index_name LIKE 'idx_cuotas_%'
    OR index_name LIKE 'idx_mora_%'
    OR index_name LIKE 'idx_cartera_%'
    OR index_name LIKE 'idx_direcciones_%'
    OR index_name LIKE 'idx_sucursal_%'
    OR index_name LIKE 'idx_gestiones_%'
    OR index_name LIKE 'idx_compromisos_%'
    OR index_name LIKE 'idx_convenios_%'
    OR index_name LIKE 'idx_cuota_convenio_%'
    OR index_name LIKE 'idx_personas_%'
  )
GROUP BY table_name, index_name, index_type
ORDER BY table_name, index_name;

-- =====================================================
-- ESTADÍSTICAS DE OPTIMIZACIÓN
-- =====================================================

SELECT '📊 Estadísticas de tablas optimizadas...' AS 'Estado';

SELECT
    table_name AS 'Tabla',
    table_rows AS 'Filas Aprox.',
    ROUND(data_length / 1024 / 1024, 2) AS 'Tamaño (MB)',
    ROUND(index_length / 1024 / 1024, 2) AS 'Índices (MB)',
    ROUND((data_length + index_length) / 1024 / 1024, 2) AS 'Total (MB)'
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
    'cuotas', 'mora_cuota', 'cartera_jcc', 'cartera_asesor',
    'cartera_analista', 'direcciones', 'gestiones', 'compromisos',
    'convenios', 'cuota_convenio_models', 'personas'
  )
ORDER BY table_rows DESC;

-- =====================================================
-- LIMPIAR
-- =====================================================

-- Eliminar el procedimiento
DROP PROCEDURE IF EXISTS create_index_safe;

SELECT '✅ Optimización completada exitosamente!' AS 'Estado';
SELECT '⚡ Los índices están listos para mejorar el rendimiento.' AS 'Estado';
SELECT '📝 Revisa el README para más información: OPTIMIZACION_DEUDAS_README.md' AS 'Estado';

-- =====================================================
-- RECOMENDACIONES POST-INSTALACIÓN
-- =====================================================

SELECT '💡 RECOMENDACIONES:' AS '';
SELECT '1. Ejecuta: php artisan cache:clear' AS 'Acción';
SELECT '2. Ejecuta: php artisan view:clear' AS 'Acción';
SELECT '3. Prueba la vista /admin/deudas' AS 'Acción';
SELECT '4. Monitorea los logs para ver tiempos de ejecución' AS 'Acción';
SELECT '5. Considera optimizar tablas cada 3-6 meses con: OPTIMIZE TABLE cuotas;' AS 'Acción';
