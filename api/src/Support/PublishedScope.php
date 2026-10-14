<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Fragmento SQL reutilizable: solo contenido publicado y vigente.
 * Los endpoints publicos DEBEN aplicarlo; admin no.
 */
final class PublishedScope
{
    public static function sql(string $alias = ''): string
    {
        $p = $alias === '' ? '' : $alias . '.';
        return sprintf(
            "%sestado = 'publicado' AND (%spublicado_en IS NULL OR %spublicado_en <= UTC_TIMESTAMP())",
            $p,
            $p,
            $p
        );
    }

    /** Variante para avisos con ventana visible_desde/hasta. */
    public static function avisoSql(string $alias = ''): string
    {
        $p = $alias === '' ? '' : $alias . '.';
        return sprintf(
            "%sestado = 'publicado'
             AND (%spublicado_en IS NULL OR %spublicado_en <= UTC_TIMESTAMP())
             AND (%svisible_desde IS NULL OR %svisible_desde <= UTC_TIMESTAMP())
             AND (%svisible_hasta IS NULL OR %svisible_hasta >= UTC_TIMESTAMP())",
            $p, $p, $p, $p, $p, $p, $p
        );
    }
}
