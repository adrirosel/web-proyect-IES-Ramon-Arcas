<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\EditorialCrud;
use App\Http\Request;

final class NoticiasController
{
    use EditorialCrud;

    protected function table(): string
    {
        return 'noticias';
    }

    protected function writable(): array
    {
        return [
            'slug', 'slug_en', 'titulo_es', 'titulo_en', 'resumen_es', 'resumen_en',
            'cuerpo_es', 'cuerpo_en', 'categoria', 'imagen_id', 'estado', 'publicado_en',
            'destacado', 'curso_academico_id', 'autor_id', 'actualizado_por',
        ];
    }

    protected function sortColumns(): array
    {
        return ['id', 'publicado_en', 'titulo_es', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'publicado_en';
    }

    protected function publicFilters(Request $request): array
    {
        $f = [];
        if ($c = $request->query('categoria')) {
            $f['categoria = ?'] = $c;
        }
        if ($ca = $request->query('curso_academico')) {
            $f['curso_academico_id = ?'] = (int) $ca;
        }
        return $f;
    }
}
