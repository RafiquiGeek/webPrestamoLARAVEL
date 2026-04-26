-- =====================================================
-- Script para insertar Departamentos, Provincias y Distritos de Perú
-- Catálogo de Ubigeos SUNAT
-- =====================================================

-- Primero, crear las tablas si no existen
CREATE TABLE IF NOT EXISTS departments (
    id VARCHAR(2) PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provinces (
    id VARCHAR(4) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department_id VARCHAR(2) NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS districts (
    id VARCHAR(6) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    province_id VARCHAR(4) NOT NULL,
    department_id VARCHAR(2) NOT NULL,
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limpiar tablas existentes (opcional - comentar si no quieres borrar datos)
-- TRUNCATE TABLE districts;
-- TRUNCATE TABLE provinces;
-- TRUNCATE TABLE departments;

-- =====================================================
-- INSERTAR DEPARTAMENTOS
-- =====================================================
INSERT INTO departments (id, name) VALUES
('01', 'Amazonas'),
('02', 'Áncash'),
('03', 'Apurímac'),
('04', 'Arequipa'),
('05', 'Ayacucho'),
('06', 'Cajamarca'),
('07', 'Callao'),
('08', 'Cusco'),
('09', 'Huancavelica'),
('10', 'Huánuco'),
('11', 'Ica'),
('12', 'Junín'),
('13', 'La Libertad'),
('14', 'Lambayeque'),
('15', 'Lima'),
('16', 'Loreto'),
('17', 'Madre de Dios'),
('18', 'Moquegua'),
('19', 'Pasco'),
('20', 'Piura'),
('21', 'Puno'),
('22', 'San Martín'),
('23', 'Tacna'),
('24', 'Tumbes'),
('25', 'Ucayali')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- =====================================================
-- INSERTAR PROVINCIAS
-- =====================================================
