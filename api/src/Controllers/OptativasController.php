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
 * Materia estable + oferta por enseñanza/nivel/curso academico.
 */
final class OptativasController
{
    public function publicMaterias(Request $request, array $params): Response
    {
        $rows = Database::pdo()->query(
            "SELECT id, slug, nombre_es, nombre_en, descripcion_es, descripcion_en
             FROM materias_optativas WHERE estado = 'publicado' ORDER BY nombre_es"
        )->fetchAll();
        return Response::json(['data' => $rows]);
    }

    public function publicMateria(Request $request, array $params): Response
    {
        $stmt = Database::pdo()->prepare(
            "SELECT id, slug, nombre_es, nombre_en, descripcion_es, descripcion_en
             FROM materias_optativas WHERE slug = ? AND estado = 'publicado' LIMIT 1"
        );
        $stmt->execute([$params['slug']]);
        $m = $stmt->fetch();
        if (!$m) {
            return Response::error('not_found', 'No encontrado', 404);
        }
        $ofertas = Database::pdo()->prepare(
            "SELECT o.id, o.nivel, o.curso_academico_id, e.slug AS ensenanza_slug, e.titulo_es AS ensenanza_titulo_es,
                    ca.etiqueta AS curso_academico
             FROM ofertas_optativas o
             INNER JOIN ensenanzas e ON e.id = o.ensenanza_id AND e.estado = 'publicado'
             INNER JOIN cursos_academicos ca ON ca.id = o.curso_academico_id
             WHERE o.materia_optativa_id = ? AND o.estado = 'publicado'
             ORDER BY ca.etiqueta DESC, o.nivel"
        );
        $ofertas->execute([(int) $m['id']]);
        $m['ofertas'] = $ofertas->fetchAll();
        return Response::json(['data' => $m]);
    }

    public function publicOfertas(Request $request, array $params): Response
    {
        $where = "o.estado = 'publicado' AND e.estado = 'publicado' AND m.estado = 'publicado'";
        $bind = [];
        if ($ens = $request->query('ensenanza_id')) {
            $where .= ' AND o.ensenanza_id = ?';
            $bind[] = (int) $ens;
        }
        if ($nivel = $request->query('nivel')) {
            $where .= ' AND o.nivel = ?';
            $bind[] = $nivel;
        }
        if ($ca = $request->query('curso_academico')) {
            $where .= ' AND o.curso_academico_id = ?';
            $bind[] = (int) $ca;
        }
        $sql = "SELECT o.id, o.nivel, o.curso_academico_id, o.ensenanza_id,
                       m.slug AS materia_slug, m.nombre_es, m.nombre_en
                FROM ofertas_optativas o
                INNER JOIN materias_optativas m ON m.id = o.materia_optativa_id
                INNER JOIN ensenanzas e ON e.id = o.ensenanza_id
                WHERE $where ORDER BY m.nombre_es";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($bind);
        return Response::json(['data' => $stmt->fetchAll()]);
    }

    public function adminMateriasIndex(Request $request, array $params): Response
    {
        return Response::json(['data' => Database::pdo()->query('SELECT * FROM materias_optativas ORDER BY nombre_es')->fetchAll()]);
    }

    public function adminMateriasStore(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, ['slug', 'nombre_es', 'nombre_en', 'descripcion_es', 'descripcion_en', 'estado']);
        if (empty($data['slug']) || empty($data['nombre_es'])) {
            return Response::error('validation_error', 'slug y nombre_es requeridos', 422);
        }
        $cols = array_keys($data);
        Database::pdo()->prepare(
            'INSERT INTO materias_optativas (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')'
        )->execute(array_values($data));
        return Response::json(['id' => (int) Database::pdo()->lastInsertId()], 201);
    }

    public function adminMateriasPatch(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, ['slug', 'nombre_es', 'nombre_en', 'descripcion_es', 'descripcion_en', 'estado']);
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
        Database::pdo()->prepare('UPDATE materias_optativas SET ' . implode(',', $sets) . ' WHERE id = ?')->execute($vals);
        return Response::json(['ok' => true]);
    }

    public function adminOfertasIndex(Request $request, array $params): Response
    {
        $pg = Pagination::fromRequest($request);
        $pdo = Database::pdo();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM ofertas_optativas')->fetchColumn();
        $stmt = $pdo->prepare('SELECT * FROM ofertas_optativas ORDER BY id DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $pg->limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $pg->offset, PDO::PARAM_INT);
        $stmt->execute();
        return Response::json($pg->envelope($stmt->fetchAll(), $total));
    }

    public function adminOfertasStore(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, [
            'materia_optativa_id', 'ensenanza_id', 'nivel', 'curso_academico_id', 'estado',
        ]);
        foreach (['materia_optativa_id', 'ensenanza_id', 'nivel', 'curso_academico_id'] as $req) {
            if (empty($data[$req])) {
                return Response::error('validation_error', "$req requerido", 422);
            }
        }
        try {
            $cols = array_keys($data);
            Database::pdo()->prepare(
                'INSERT INTO ofertas_optativas (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')'
            )->execute(array_values($data));
        } catch (\PDOException) {
            return Response::error('conflict', 'Oferta duplicada o FK invalida', 409);
        }
        return Response::json(['id' => (int) Database::pdo()->lastInsertId()], 201);
    }

    public function adminOfertasPatch(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, [
            'materia_optativa_id', 'ensenanza_id', 'nivel', 'curso_academico_id', 'estado',
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
        Database::pdo()->prepare('UPDATE ofertas_optativas SET ' . implode(',', $sets) . ' WHERE id = ?')->execute($vals);
        return Response::json(['ok' => true]);
    }

    public function adminOfertasDelete(Request $request, array $params): Response
    {
        Database::pdo()->prepare('DELETE FROM ofertas_optativas WHERE id = ?')->execute([(int) $params['id']]);
        return Response::noContent();
    }
}
