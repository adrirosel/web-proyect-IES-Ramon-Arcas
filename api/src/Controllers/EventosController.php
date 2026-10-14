<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\EditorialCrud;
use App\Http\Request;

final class EventosController
{
    use EditorialCrud;

    protected function table(): string
    {
        return 'eventos';
    }

    protected function writable(): array
    {
        return [
            'slug', 'titulo_es', 'titulo_en', 'tipo', 'descripcion_es', 'descripcion_en',
            'inicio', 'fin', 'lugar', 'destinatarios', 'curso_academico_id',
            'estado', 'publicado_en', 'autor_id', 'actualizado_por',
        ];
    }

    protected function sortColumns(): array
    {
        return ['id', 'inicio', 'titulo_es'];
    }

    protected function defaultSort(): string
    {
        return 'inicio';
    }

    protected function publicFilters(Request $request): array
    {
        $f = [];
        if ($desde = $request->query('desde')) {
            $f['inicio >= ?'] = $desde;
        }
        if ($hasta = $request->query('hasta')) {
            $f['inicio <= ?'] = $hasta;
        }
        if ($tipo = $request->query('tipo')) {
            $f['tipo = ?'] = $tipo;
        }
        return $f;
    }
}
