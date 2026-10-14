<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * Matriz de permisos en servidor. Denegacion por defecto.
 * El frontend puede ocultar botones; eso NO autoriza.
 */
final class Rbac
{
    /** @var array<string, list<string>> recurso => roles */
    private const MATRIX = [
        'usuarios' => ['admin'],
        'configuracion' => ['admin'],
        'paginas' => ['admin', 'editor'],
        'menus' => ['admin', 'editor'],
        'enlaces' => ['admin', 'editor'],
        'noticias' => ['admin', 'editor'],
        'avisos' => ['admin', 'editor', 'secretaria'],
        'eventos' => ['admin', 'editor'],
        'cursos_academicos' => ['admin', 'editor'],
        'ensenanzas' => ['admin', 'editor'],
        'materias_optativas' => ['admin', 'editor'],
        'ofertas_optativas' => ['admin', 'editor'],
        'departamentos' => ['admin', 'editor'],
        'personas' => ['admin', 'editor'],
        'organos' => ['admin', 'editor'],
        'programas' => ['admin', 'editor'],
        'documentos' => ['admin', 'editor', 'secretaria'],
        'categorias_documentos' => ['admin', 'editor', 'secretaria'],
        'servicios' => ['admin', 'editor'],
        'archivos' => ['admin', 'editor', 'secretaria'],
        'contactos' => ['admin', 'secretaria'],
    ];

    public static function can(string $rol, string $recurso): bool
    {
        $allowed = self::MATRIX[$recurso] ?? [];
        return in_array($rol, $allowed, true);
    }
}
