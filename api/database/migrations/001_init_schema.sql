-- Esquema v1 web publica IES Ramon Arcas.
-- utf8mb4 + InnoDB: acentos/emoji y FKs reales.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    nombre VARCHAR(120) NOT NULL,
    rol ENUM('admin', 'editor', 'secretaria') NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Claves publicas/privadas: GET /configuracion solo lee publica=1
CREATE TABLE configuracion (
    clave VARCHAR(100) NOT NULL PRIMARY KEY,
    valor TEXT NOT NULL,
    publica TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE archivos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ruta VARCHAR(255) NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    mime VARCHAR(120) NOT NULL,
    tamano INT UNSIGNED NOT NULL,
    descripcion_es VARCHAR(255) NULL,
    descripcion_en VARCHAR(255) NULL,
    texto_alt_es VARCHAR(255) NULL,
    texto_alt_en VARCHAR(255) NULL,
    subido_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_archivos_usuario FOREIGN KEY (subido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id INT UNSIGNED NOT NULL,
    padre_id INT UNSIGNED NULL,
    titulo_es VARCHAR(160) NOT NULL,
    titulo_en VARCHAR(160) NULL,
    url VARCHAR(500) NULL,
    pagina_id INT UNSIGNED NULL,
    orden INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_menu_items_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    CONSTRAINT fk_menu_items_padre FOREIGN KEY (padre_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE paginas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    slug_en VARCHAR(160) NULL UNIQUE,
    titulo_es VARCHAR(200) NOT NULL,
    titulo_en VARCHAR(200) NULL,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'borrador',
    publicado_en DATETIME NULL,
    autor_id INT UNSIGNED NULL,
    actualizado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_paginas_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_paginas_actualizado FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK diferida: pagina_id en menu_items apunta a paginas
ALTER TABLE menu_items
    ADD CONSTRAINT fk_menu_items_pagina FOREIGN KEY (pagina_id) REFERENCES paginas(id) ON DELETE SET NULL;

CREATE TABLE pagina_bloques (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pagina_id INT UNSIGNED NOT NULL,
    tipo ENUM('texto', 'imagen', 'documentos', 'enlaces') NOT NULL,
    contenido_json JSON NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_bloques_pagina FOREIGN KEY (pagina_id) REFERENCES paginas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enlaces (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grupo VARCHAR(80) NOT NULL,
    titulo_es VARCHAR(160) NOT NULL,
    titulo_en VARCHAR(160) NULL,
    url VARCHAR(500) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'publicado',
    INDEX idx_enlaces_grupo (grupo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cursos_academicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etiqueta VARCHAR(20) NOT NULL UNIQUE,
    inicio DATE NOT NULL,
    fin DATE NOT NULL,
    actual TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE noticias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    slug_en VARCHAR(160) NULL UNIQUE,
    titulo_es VARCHAR(200) NOT NULL,
    titulo_en VARCHAR(200) NULL,
    resumen_es TEXT NULL,
    resumen_en TEXT NULL,
    cuerpo_es MEDIUMTEXT NOT NULL,
    cuerpo_en MEDIUMTEXT NULL,
    categoria VARCHAR(80) NULL,
    imagen_id INT UNSIGNED NULL,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'borrador',
    publicado_en DATETIME NULL,
    destacado TINYINT(1) NOT NULL DEFAULT 0,
    curso_academico_id INT UNSIGNED NULL,
    autor_id INT UNSIGNED NULL,
    actualizado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_noticias_imagen FOREIGN KEY (imagen_id) REFERENCES archivos(id) ON DELETE SET NULL,
    CONSTRAINT fk_noticias_curso FOREIGN KEY (curso_academico_id) REFERENCES cursos_academicos(id) ON DELETE SET NULL,
    CONSTRAINT fk_noticias_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_noticias_actualizado FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avisos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo_es VARCHAR(200) NOT NULL,
    titulo_en VARCHAR(200) NULL,
    texto_es TEXT NOT NULL,
    texto_en TEXT NULL,
    prioridad ENUM('baja', 'media', 'alta') NOT NULL DEFAULT 'media',
    ubicacion VARCHAR(80) NOT NULL DEFAULT 'portada',
    destinatarios VARCHAR(120) NULL,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'borrador',
    visible_desde DATETIME NULL,
    visible_hasta DATETIME NULL,
    publicado_en DATETIME NULL,
    autor_id INT UNSIGNED NULL,
    actualizado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_avisos_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_avisos_actualizado FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ensenanzas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    slug_en VARCHAR(160) NULL UNIQUE,
    tipo ENUM('eso', 'bachillerato', 'fp', 'otro') NOT NULL,
    familia VARCHAR(120) NULL,
    titulo_es VARCHAR(200) NOT NULL,
    titulo_en VARCHAR(200) NULL,
    requisitos_es TEXT NULL,
    requisitos_en TEXT NULL,
    duracion_es VARCHAR(120) NULL,
    duracion_en VARCHAR(120) NULL,
    salidas_es TEXT NULL,
    salidas_en TEXT NULL,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'borrador',
    publicado_en DATETIME NULL,
    orden INT NOT NULL DEFAULT 0,
    autor_id INT UNSIGNED NULL,
    actualizado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ensenanzas_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_ensenanzas_actualizado FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE modulos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ensenanza_id INT UNSIGNED NOT NULL,
    nombre_es VARCHAR(200) NOT NULL,
    nombre_en VARCHAR(200) NULL,
    horas INT UNSIGNED NULL,
    orden INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_modulos_ensenanza FOREIGN KEY (ensenanza_id) REFERENCES ensenanzas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Materia estable (no se duplica cada curso academico)
CREATE TABLE materias_optativas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    nombre_es VARCHAR(200) NOT NULL,
    nombre_en VARCHAR(200) NULL,
    descripcion_es TEXT NULL,
    descripcion_en TEXT NULL,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'publicado',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Oferta concreta: enseñanza + nivel + periodo
CREATE TABLE ofertas_optativas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    materia_optativa_id INT UNSIGNED NOT NULL,
    ensenanza_id INT UNSIGNED NOT NULL,
    nivel VARCHAR(40) NOT NULL,
    curso_academico_id INT UNSIGNED NOT NULL,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'publicado',
    UNIQUE KEY uq_oferta (materia_optativa_id, ensenanza_id, nivel, curso_academico_id),
    CONSTRAINT fk_oferta_materia FOREIGN KEY (materia_optativa_id) REFERENCES materias_optativas(id) ON DELETE CASCADE,
    CONSTRAINT fk_oferta_ensenanza FOREIGN KEY (ensenanza_id) REFERENCES ensenanzas(id) ON DELETE CASCADE,
    CONSTRAINT fk_oferta_curso FOREIGN KEY (curso_academico_id) REFERENCES cursos_academicos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE departamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    nombre_es VARCHAR(200) NOT NULL,
    nombre_en VARCHAR(200) NULL,
    presentacion_es TEXT NULL,
    presentacion_en TEXT NULL,
    contacto_publico VARCHAR(255) NULL,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'publicado',
    orden INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE personas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(160) NOT NULL,
    foto_id INT UNSIGNED NULL,
    biografia_es TEXT NULL,
    biografia_en TEXT NULL,
    visible_publico TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_personas_foto FOREIGN KEY (foto_id) REFERENCES archivos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE persona_departamento (
    persona_id INT UNSIGNED NOT NULL,
    departamento_id INT UNSIGNED NOT NULL,
    cargo_es VARCHAR(160) NULL,
    cargo_en VARCHAR(160) NULL,
    orden INT NOT NULL DEFAULT 0,
    PRIMARY KEY (persona_id, departamento_id),
    CONSTRAINT fk_pd_persona FOREIGN KEY (persona_id) REFERENCES personas(id) ON DELETE CASCADE,
    CONSTRAINT fk_pd_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE organos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    tipo ENUM('equipo_directivo', 'consejo_escolar', 'claustro', 'ccp', 'otro') NOT NULL,
    nombre_es VARCHAR(200) NOT NULL,
    nombre_en VARCHAR(200) NULL,
    descripcion_es TEXT NULL,
    descripcion_en TEXT NULL,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'publicado',
    orden INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cargo en el vinculo: la misma persona puede ser docente y secretario
CREATE TABLE organo_miembros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organo_id INT UNSIGNED NOT NULL,
    persona_id INT UNSIGNED NOT NULL,
    cargo_es VARCHAR(160) NOT NULL,
    cargo_en VARCHAR(160) NULL,
    orden INT NOT NULL DEFAULT 0,
    curso_academico_id INT UNSIGNED NULL,
    vigente_desde DATE NULL,
    vigente_hasta DATE NULL,
    CONSTRAINT fk_om_organo FOREIGN KEY (organo_id) REFERENCES organos(id) ON DELETE CASCADE,
    CONSTRAINT fk_om_persona FOREIGN KEY (persona_id) REFERENCES personas(id) ON DELETE CASCADE,
    CONSTRAINT fk_om_curso FOREIGN KEY (curso_academico_id) REFERENCES cursos_academicos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE programas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    slug_en VARCHAR(160) NULL UNIQUE,
    categoria ENUM('educativo', 'plan_centro', 'actividad', 'otro') NOT NULL DEFAULT 'educativo',
    titulo_es VARCHAR(200) NOT NULL,
    titulo_en VARCHAR(200) NULL,
    descripcion_es TEXT NULL,
    descripcion_en TEXT NULL,
    documento_id INT UNSIGNED NULL,
    imagen_id INT UNSIGNED NULL,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'borrador',
    publicado_en DATETIME NULL,
    orden INT NOT NULL DEFAULT 0,
    autor_id INT UNSIGNED NULL,
    actualizado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_programas_doc FOREIGN KEY (documento_id) REFERENCES archivos(id) ON DELETE SET NULL,
    CONSTRAINT fk_programas_img FOREIGN KEY (imagen_id) REFERENCES archivos(id) ON DELETE SET NULL,
    CONSTRAINT fk_programas_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_programas_actualizado FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE programa_departamento (
    programa_id INT UNSIGNED NOT NULL,
    departamento_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (programa_id, departamento_id),
    CONSTRAINT fk_progdep_prog FOREIGN KEY (programa_id) REFERENCES programas(id) ON DELETE CASCADE,
    CONSTRAINT fk_progdep_dep FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE eventos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    titulo_es VARCHAR(200) NOT NULL,
    titulo_en VARCHAR(200) NULL,
    tipo VARCHAR(80) NULL,
    descripcion_es TEXT NULL,
    descripcion_en TEXT NULL,
    inicio DATETIME NOT NULL,
    fin DATETIME NULL,
    lugar VARCHAR(200) NULL,
    destinatarios VARCHAR(120) NULL,
    curso_academico_id INT UNSIGNED NULL,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'borrador',
    publicado_en DATETIME NULL,
    autor_id INT UNSIGNED NULL,
    actualizado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_eventos_curso FOREIGN KEY (curso_academico_id) REFERENCES cursos_academicos(id) ON DELETE SET NULL,
    CONSTRAINT fk_eventos_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_eventos_actualizado FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categorias_documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    nombre_es VARCHAR(160) NOT NULL,
    nombre_en VARCHAR(160) NULL,
    orden INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo_es VARCHAR(200) NOT NULL,
    titulo_en VARCHAR(200) NULL,
    descripcion_es TEXT NULL,
    descripcion_en TEXT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    curso_academico_id INT UNSIGNED NULL,
    ensenanza_id INT UNSIGNED NULL,
    archivo_id INT UNSIGNED NOT NULL,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'borrador',
    publicado_en DATETIME NULL,
    autor_id INT UNSIGNED NULL,
    actualizado_por INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_docs_cat FOREIGN KEY (categoria_id) REFERENCES categorias_documentos(id),
    CONSTRAINT fk_docs_curso FOREIGN KEY (curso_academico_id) REFERENCES cursos_academicos(id) ON DELETE SET NULL,
    CONSTRAINT fk_docs_ens FOREIGN KEY (ensenanza_id) REFERENCES ensenanzas(id) ON DELETE SET NULL,
    CONSTRAINT fk_docs_arch FOREIGN KEY (archivo_id) REFERENCES archivos(id),
    CONSTRAINT fk_docs_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_docs_actualizado FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE servicios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    nombre_es VARCHAR(200) NOT NULL,
    nombre_en VARCHAR(200) NULL,
    horario_es TEXT NULL,
    horario_en TEXT NULL,
    condiciones_es TEXT NULL,
    condiciones_en TEXT NULL,
    contacto_publico VARCHAR(255) NULL,
    estado ENUM('borrador', 'publicado', 'archivado') NOT NULL DEFAULT 'publicado',
    orden INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contactos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    tipo ENUM('consulta', 'sugerencia', 'queja', 'rincon_pensar') NOT NULL,
    mensaje TEXT NOT NULL,
    consentimiento_privacidad TINYINT(1) NOT NULL,
    estado ENUM('pendiente', 'en_curso', 'finalizado') NOT NULL DEFAULT 'pendiente',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contactos_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limit por IP+ruta (login y contacto)
CREATE TABLE rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(190) NOT NULL,
    intentos INT UNSIGNED NOT NULL DEFAULT 1,
    ventana_inicio DATETIME NOT NULL,
    INDEX idx_rate_clave (clave, ventana_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    evento VARCHAR(80) NOT NULL,
    detalle VARCHAR(500) NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_evento (evento, created_at),
    CONSTRAINT fk_audit_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
