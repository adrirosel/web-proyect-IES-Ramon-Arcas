<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\JsonBody;
use App\Support\Pagination;
use PDO;

/**
 * Personas y organos: un individuo, varios contextos (cargo en el vinculo).
 * Endpoints publicos sin email/telefono privados.
 */
final class OrganizacionController
{
    public function publicOrganos(Request $request, array $params): Response
    {
        $rows = Database::pdo()->query(
            "SELECT id, slug, tipo, nombre_es, nombre_en, descripcion_es, descripcion_en, orden
             FROM organos WHERE estado = 'publicado' ORDER BY orden, id"
        )->fetchAll();
        return Response::json(['data' => $rows]);
    }

    public function publicOrgano(Request $request, array $params): Response
    {
        $stmt = Database::pdo()->prepare(
            "SELECT id, slug, tipo, nombre_es, nombre_en, descripcion_es, descripcion_en
             FROM organos WHERE slug = ? AND estado = 'publicado' LIMIT 1"
        );
        $stmt->execute([$params['slug']]);
        $org = $stmt->fetch();
        if (!$org) {
            return Response::error('not_found', 'No encontrado', 404);
        }
        $miembros = Database::pdo()->prepare(
            'SELECT om.id, om.cargo_es, om.cargo_en, om.orden, p.id AS persona_id, p.nombre, p.foto_id
             FROM organo_miembros om
             INNER JOIN personas p ON p.id = om.persona_id
             WHERE om.organo_id = ? AND p.visible_publico = 1
               AND (om.vigente_hasta IS NULL OR om.vigente_hasta >= UTC_DATE())
             ORDER BY om.orden, p.nombre'
        );
        $miembros->execute([(int) $org['id']]);
        $org['miembros'] = $miembros->fetchAll();
        return Response::json(['data' => $org]);
    }

    public function publicPersonas(Request $request, array $params): Response
    {
        $pg = Pagination::fromRequest($request);
        $pdo = Database::pdo();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM personas WHERE visible_publico = 1')->fetchColumn();
        $stmt = $pdo->prepare(
            'SELECT id, nombre, foto_id, biografia_es, biografia_en
             FROM personas WHERE visible_publico = 1 ORDER BY nombre LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $pg->limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $pg->offset, PDO::PARAM_INT);
        $stmt->execute();
        return Response::json($pg->envelope($stmt->fetchAll(), $total));
    }

    // --- Admin personas ---
    public function adminPersonasIndex(Request $request, array $params): Response
    {
        $rows = Database::pdo()->query('SELECT * FROM personas ORDER BY nombre')->fetchAll();
        return Response::json(['data' => $rows]);
    }

    public function adminPersonasStore(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, ['nombre', 'foto_id', 'biografia_es', 'biografia_en', 'visible_publico']);
        if (empty($data['nombre'])) {
            return Response::error('validation_error', 'nombre requerido', 422);
        }
        $cols = array_keys($data);
        $sql = 'INSERT INTO personas (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
        Database::pdo()->prepare($sql)->execute(array_values($data));
        return Response::json(['id' => (int) Database::pdo()->lastInsertId()], 201);
    }

    public function adminPersonasPatch(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, ['nombre', 'foto_id', 'biografia_es', 'biografia_en', 'visible_publico']);
        if ($data === []) {
            return Response::error('validation_error', 'Sin campos', 422);
        }
        $sets = [];
        $vals = [];
        foreach ($data as $k => $v) {
            $sets[] = "$k = ?";
            $vals[] = $v;
        }
        $vals[] = (int) $params['id'];
        Database::pdo()->prepare('UPDATE personas SET ' . implode(',', $sets) . ' WHERE id = ?')->execute($vals);
        return Response::json(['ok' => true]);
    }

    public function adminPersonasDelete(Request $request, array $params): Response
    {
        Database::pdo()->prepare('DELETE FROM personas WHERE id = ?')->execute([(int) $params['id']]);
        return Response::noContent();
    }

    public function adminSyncDepartamentos(Request $request, array $params): Response
    {
        $personaId = (int) $params['id'];
        $items = $request->body['departamentos'] ?? null;
        if (!is_array($items)) {
            return Response::error('validation_error', 'departamentos[] requerido', 422);
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM persona_departamento WHERE persona_id = ?')->execute([$personaId]);
            $ins = $pdo->prepare(
                'INSERT INTO persona_departamento (persona_id, departamento_id, cargo_es, cargo_en, orden) VALUES (?,?,?,?,?)'
            );
            foreach ($items as $i => $item) {
                $ins->execute([
                    $personaId,
                    (int) $item['departamento_id'],
                    $item['cargo_es'] ?? null,
                    $item['cargo_en'] ?? null,
                    $item['orden'] ?? $i,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable) {
            $pdo->rollBack();
            return Response::error('conflict', 'Vinculos no validos', 409);
        }
        return Response::json(['ok' => true]);
    }

    // --- Admin organos ---
    public function adminOrganosIndex(Request $request, array $params): Response
    {
        return Response::json(['data' => Database::pdo()->query('SELECT * FROM organos ORDER BY orden')->fetchAll()]);
    }

    public function adminOrganosStore(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, [
            'slug', 'tipo', 'nombre_es', 'nombre_en', 'descripcion_es', 'descripcion_en', 'estado', 'orden',
        ]);
        if (empty($data['slug']) || empty($data['nombre_es']) || empty($data['tipo'])) {
            return Response::error('validation_error', 'slug, tipo y nombre_es requeridos', 422);
        }
        $cols = array_keys($data);
        $sql = 'INSERT INTO organos (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
        Database::pdo()->prepare($sql)->execute(array_values($data));
        return Response::json(['id' => (int) Database::pdo()->lastInsertId()], 201);
    }

    public function adminOrganosPatch(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, [
            'slug', 'tipo', 'nombre_es', 'nombre_en', 'descripcion_es', 'descripcion_en', 'estado', 'orden',
        ]);
        $sets = [];
        $vals = [];
        foreach ($data as $k => $v) {
            $sets[] = "$k = ?";
            $vals[] = $v;
        }
        if ($sets === []) {
            return Response::error('validation_error', 'Sin campos', 422);
        }
        $vals[] = (int) $params['id'];
        Database::pdo()->prepare('UPDATE organos SET ' . implode(',', $sets) . ' WHERE id = ?')->execute($vals);
        return Response::json(['ok' => true]);
    }

    public function adminOrganosDelete(Request $request, array $params): Response
    {
        Database::pdo()->prepare("UPDATE organos SET estado = 'archivado' WHERE id = ?")
            ->execute([(int) $params['id']]);
        return Response::noContent();
    }

    public function adminSyncMiembros(Request $request, array $params): Response
    {
        $organoId = (int) $params['id'];
        $miembros = $request->body['miembros'] ?? null;
        if (!is_array($miembros)) {
            return Response::error('validation_error', 'miembros[] requerido', 422);
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM organo_miembros WHERE organo_id = ?')->execute([$organoId]);
            $ins = $pdo->prepare(
                'INSERT INTO organo_miembros
                 (organo_id, persona_id, cargo_es, cargo_en, orden, curso_academico_id, vigente_desde, vigente_hasta)
                 VALUES (?,?,?,?,?,?,?,?)'
            );
            foreach ($miembros as $i => $m) {
                $ins->execute([
                    $organoId,
                    (int) $m['persona_id'],
                    $m['cargo_es'] ?? '',
                    $m['cargo_en'] ?? null,
                    $m['orden'] ?? $i,
                    $m['curso_academico_id'] ?? null,
                    $m['vigente_desde'] ?? null,
                    $m['vigente_hasta'] ?? null,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable) {
            $pdo->rollBack();
            return Response::error('conflict', 'Miembros no validos', 409);
        }
        return Response::json(['ok' => true]);
    }
}
