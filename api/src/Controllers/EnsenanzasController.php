<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\EditorialCrud;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\PublishedScope;

final class EnsenanzasController
{
    use EditorialCrud;

    protected function table(): string
    {
        return 'ensenanzas';
    }

    protected function writable(): array
    {
        return [
            'slug', 'slug_en', 'tipo', 'familia', 'titulo_es', 'titulo_en',
            'requisitos_es', 'requisitos_en', 'duracion_es', 'duracion_en',
            'salidas_es', 'salidas_en', 'estado', 'publicado_en', 'orden',
            'autor_id', 'actualizado_por',
        ];
    }

    protected function sortColumns(): array
    {
        return ['id', 'orden', 'titulo_es', 'tipo'];
    }

    protected function defaultSort(): string
    {
        return 'orden';
    }

    protected function publicFilters(Request $request): array
    {
        $f = [];
        if ($t = $request->query('tipo')) {
            $f['tipo = ?'] = $t;
        }
        if ($fam = $request->query('familia')) {
            $f['familia = ?'] = $fam;
        }
        return $f;
    }

    public function publicModulos(Request $request, array $params): Response
    {
        $slug = $params['slug'];
        $ens = Database::pdo()->prepare(
            'SELECT id FROM ensenanzas WHERE slug = ? AND ' . PublishedScope::sql()
        );
        $ens->execute([$slug]);
        $id = $ens->fetchColumn();
        if (!$id) {
            return Response::error('not_found', 'No encontrado', 404);
        }
        $mods = Database::pdo()->prepare(
            'SELECT id, nombre_es, nombre_en, horas, orden FROM modulos WHERE ensenanza_id = ? ORDER BY orden'
        );
        $mods->execute([(int) $id]);
        return Response::json(['data' => $mods->fetchAll()]);
    }

    public function publicDocumentos(Request $request, array $params): Response
    {
        $slug = $params['slug'];
        $ens = Database::pdo()->prepare(
            'SELECT id FROM ensenanzas WHERE slug = ? AND ' . PublishedScope::sql()
        );
        $ens->execute([$slug]);
        $id = $ens->fetchColumn();
        if (!$id) {
            return Response::error('not_found', 'No encontrado', 404);
        }
        $docs = Database::pdo()->prepare(
            'SELECT d.id, d.titulo_es, d.titulo_en, d.descripcion_es, d.descripcion_en, d.archivo_id, d.publicado_en
             FROM documentos d
             WHERE d.ensenanza_id = ? AND ' . PublishedScope::sql('d') . '
             ORDER BY d.id DESC'
        );
        $docs->execute([(int) $id]);
        return Response::json(['data' => $docs->fetchAll()]);
    }

    public function adminModulosPut(Request $request, array $params): Response
    {
        $ensenanzaId = (int) $params['id'];
        $modulos = $request->body['modulos'] ?? null;
        if (!is_array($modulos)) {
            return Response::error('validation_error', 'modulos[] requerido', 422);
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM modulos WHERE ensenanza_id = ?')->execute([$ensenanzaId]);
            $ins = $pdo->prepare(
                'INSERT INTO modulos (ensenanza_id, nombre_es, nombre_en, horas, orden) VALUES (?,?,?,?,?)'
            );
            foreach ($modulos as $i => $m) {
                $ins->execute([
                    $ensenanzaId,
                    $m['nombre_es'] ?? '',
                    $m['nombre_en'] ?? null,
                    $m['horas'] ?? null,
                    $m['orden'] ?? $i,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable) {
            $pdo->rollBack();
            return Response::error('validation_error', 'Modulos no validos', 422);
        }
        return Response::json(['ok' => true]);
    }
}
