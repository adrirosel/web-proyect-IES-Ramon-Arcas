<?php

declare(strict_types=1);

/**
 * Seed de desarrollo. Password admin SOLO desde variables de entorno.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Auth\Password;
use App\Config;
use App\Database;

$envFile = dirname(__DIR__, 2) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\"'");
        putenv(trim($k) . '=' . trim($v, " \t\"'"));
    }
}

$pdo = Database::pdo();

$email = Config::get('ADMIN_EMAIL', 'admin@iesramonarcas.local');
$password = Config::get('ADMIN_PASSWORD');
$name = Config::get('ADMIN_NAME', 'Administrador');
if ($password === null || $password === '') {
    fwrite(STDERR, "ADMIN_PASSWORD no definido en .env\n");
    exit(1);
}

$exists = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
$exists->execute([$email]);
if (!$exists->fetch()) {
    $pdo->prepare(
        'INSERT INTO usuarios (email, nombre, rol, password_hash, activo) VALUES (?, ?, \'admin\', ?, 1)'
    )->execute([$email, $name, Password::hash($password)]);
    echo "admin user created\n";
} else {
    echo "admin user exists\n";
}

$configs = [
    ['nombre_centro', 'IES Ramon Arcas Meca', 1],
    ['localizacion', 'Totana, Murcia', 1],
    ['email_contacto', 'info@iesramonarcas.example', 1],
    ['telefono', '+34 968 000 000', 1],
    ['intranet_url', 'https://intranet.ejemplo.local', 1],
    ['horario_general', 'L-V 8:00-14:30', 1],
];
$ins = $pdo->prepare(
    'INSERT INTO configuracion (clave, valor, publica) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE valor = VALUES(valor), publica = VALUES(publica)'
);
foreach ($configs as $c) {
    $ins->execute($c);
}

$pdo->exec("INSERT IGNORE INTO menus (id, clave, nombre) VALUES (1, 'principal', 'Menu principal'), (2, 'pie', 'Pie de pagina')");

$pdo->exec("INSERT IGNORE INTO cursos_academicos (id, etiqueta, inicio, fin, actual)
            VALUES (1, '2025-2026', '2025-09-01', '2026-06-30', 1)");

$pdo->exec("INSERT IGNORE INTO categorias_documentos (id, slug, nombre_es, nombre_en, orden) VALUES
    (1, 'institucional', 'Documentos institucionales', 'Institutional documents', 1),
    (2, 'familias', 'Documentos para familias', 'Family documents', 2),
    (3, 'horarios', 'Horarios', 'Timetables', 3),
    (4, 'secretaria', 'Secretaria', 'Secretary', 4)");

// Pagina y noticia de ejemplo publicadas (antes del menú: menu_items.pagina_id FK)
$pdo->exec("INSERT IGNORE INTO paginas (id, slug, titulo_es, titulo_en, estado, publicado_en)
            VALUES (1, 'quienes-somos', 'Quienes somos', 'About us', 'publicado', UTC_TIMESTAMP())");
$pdo->exec("INSERT IGNORE INTO pagina_bloques (id, pagina_id, tipo, contenido_json, orden)
            VALUES (1, 1, 'texto', '{\"markdown_es\":\"Centro educativo en Totana.\",\"markdown_en\":\"School in Totana.\"}', 0)");

// Mapa de secciones de la memoria (§6.2) como árbol demostrable sin front.
// «Acceso intranet» deja url vacía: el front resuelve configuracion.intranet_url.
$pdo->exec("INSERT IGNORE INTO menu_items (id, menu_id, padre_id, titulo_es, titulo_en, url, pagina_id, orden) VALUES
    (1, 1, NULL, 'Inicio', 'Home', '/', NULL, 1),
    (2, 1, NULL, 'Organizacion', 'Organisation', '/organizacion', NULL, 2),
    (3, 1, 2, 'Quienes somos', 'About us', NULL, 1, 1),
    (4, 1, 2, 'Equipo directivo', 'Management team', '/organizacion/equipo-directivo', NULL, 2),
    (5, 1, 2, 'Departamentos', 'Departments', '/organizacion/departamentos', NULL, 3),
    (6, 1, NULL, 'Familias', 'Families', '/familias', NULL, 3),
    (7, 1, 6, 'Documentos', 'Documents', '/familias/documentos', NULL, 1),
    (8, 1, 6, 'Enlaces', 'Links', '/familias/enlaces', NULL, 2),
    (9, 1, NULL, 'Ensenanzas', 'Studies', '/ensenanzas', NULL, 4),
    (10, 1, NULL, 'Programas', 'Programmes', '/programas', NULL, 5),
    (11, 1, NULL, 'Secretaria', 'Secretary', '/secretaria', NULL, 6),
    (12, 1, NULL, 'Noticias', 'News', '/noticias', NULL, 7),
    (13, 1, NULL, 'Contacto', 'Contact', '/contacto', NULL, 8),
    (14, 2, NULL, 'Acceso intranet', 'Intranet access', NULL, NULL, 1)");

$pdo->exec("INSERT IGNORE INTO noticias (id, slug, titulo_es, titulo_en, resumen_es, cuerpo_es, categoria, estado, publicado_en, curso_academico_id)
            VALUES (1, 'bienvenida-curso', 'Bienvenida al curso', 'Welcome to the school year',
                    'Arranca el nuevo curso', 'Contenido de bienvenida del centro.', 'general',
                    'publicado', UTC_TIMESTAMP(), 1)");

$pdo->exec("INSERT IGNORE INTO ensenanzas (id, slug, tipo, titulo_es, titulo_en, estado, publicado_en, orden)
            VALUES (1, 'eso', 'eso', 'Educacion Secundaria Obligatoria', 'Compulsory Secondary Education', 'publicado', UTC_TIMESTAMP(), 1),
                   (2, 'bachillerato', 'bachillerato', 'Bachillerato', 'Baccalaureate', 'publicado', UTC_TIMESTAMP(), 2)");

$pdo->exec("INSERT IGNORE INTO departamentos (id, slug, nombre_es, nombre_en, estado, orden)
            VALUES (1, 'matematicas', 'Matematicas', 'Mathematics', 'publicado', 1)");

$pdo->exec("INSERT IGNORE INTO organos (id, slug, tipo, nombre_es, nombre_en, estado, orden)
            VALUES (1, 'equipo-directivo', 'equipo_directivo', 'Equipo directivo', 'Management team', 'publicado', 1)");

$pdo->exec("INSERT IGNORE INTO programas (id, slug, categoria, titulo_es, titulo_en, descripcion_es, estado, publicado_en, orden)
            VALUES (1, 'erasmus-plus', 'educativo', 'Erasmus+', 'Erasmus+', 'Programa europeo de movilidad.', 'publicado', UTC_TIMESTAMP(), 1)");

$pdo->exec("INSERT IGNORE INTO materias_optativas (id, slug, nombre_es, nombre_en, estado)
            VALUES (1, 'robotica', 'Robotica', 'Robotics', 'publicado')");

$pdo->exec("INSERT IGNORE INTO ofertas_optativas (id, materia_optativa_id, ensenanza_id, nivel, curso_academico_id, estado)
            VALUES (1, 1, 1, '3ESO', 1, 'publicado')");

$pdo->exec("INSERT IGNORE INTO servicios (id, slug, nombre_es, nombre_en, horario_es, estado, orden)
            VALUES (1, 'biblioteca', 'Biblioteca', 'Library', 'L-V 9:00-13:00', 'publicado', 1)");

$pdo->exec("INSERT IGNORE INTO enlaces (id, grupo, titulo_es, titulo_en, url, orden, estado)
            VALUES (1, 'plataformas', 'Aula virtual', 'Virtual classroom', 'https://aula.ejemplo.local', 1, 'publicado')");

echo "seed OK\n";
