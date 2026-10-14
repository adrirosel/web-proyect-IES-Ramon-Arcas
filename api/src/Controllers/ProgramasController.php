<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\EditorialCrud;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\PublishedScope;

final class ProgramasController
{
    use EditorialCrud;

    protected function table(): string
    {
        return 'programas';
    }

    protected function writable(): array
    {
        return [
            'slug', 'slug_en', 'categoria', 'titulo_es', 'titulo_en',
            'descripcion_es', 'descripcion_en', 'documento_id', 'imagen_id',
            'estado', 'publicado_en', 'orden', 'autor_id', 'actualizado_por',
        ];
    }

    protected function sortColumns(): array
    {
        return ['id', 'orden', 'titulo_es', 'categoria'];
    }

    protected function defaultSort(): string
    {
        return 'orden';
    }

    protected function publicFilters(Request $request): array
    {
        $f = [];
        if ($c = $request->query('categoria')) {
            $f['categoria = ?'] = $c;
        }
        return $f;
    }

    public function publicShow(Request $request, array $params): Response
    {
        $slug = $params['slug'];
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM programas WHERE slug = ? AND ' . PublishedScope::sql() . ' LIMIT 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return Response::error('not_found', 'No encontrado', 404);
        }
        $deps = Database::pdo()->prepare(
            'SELECT d.id, d.slug, d.nombre_es, d.nombre_en
             FROM departamentos d
             INNER JOIN programa_departamento pd ON pd.departamento_id = d.id
             WHERE pd.programa_id = ? AND d.estado = \'publicado\'
             ORDER BY d.orden'
        );
        $deps->execute([(int) $row['id']]);
        $row['departamentos'] = $deps->fetchAll();
        return Response::json(['data' => $row]);
    }

    public function adminSyncDepartamentos(Request $request, array $params): Response
    {
        $programaId = (int) $params['id'];
        $ids = $request->body['departamento_ids'] ?? null;
        if (!is_array($ids)) {
            return Response::error('validation_error', 'departamento_ids[] requerido', 422);
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM programa_departamento WHERE programa_id = ?')->execute([$programaId]);
            $ins = $pdo->prepare(
                'INSERT INTO programa_departamento (programa_id, departamento_id) VALUES (?, ?)'
            );
            foreach ($ids as $depId) {
                $ins->execute([$programaId, (int) $depId]);
            }
            $pdo->commit();
        } catch (\Throwable) {
            $pdo->rollBack();
            return Response::error('conflict', 'Departamentos no validos', 409);
        }
        return Response::json(['ok' => true]);
    }
}
