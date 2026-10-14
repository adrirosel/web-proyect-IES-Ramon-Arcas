<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\SessionAuth;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\JsonBody;
use App\Support\Pagination;
use PDO;

final class ServiciosController
{
    public function publicIndex(Request $request, array $params): Response
    {
        $rows = Database::pdo()->query(
            "SELECT id, slug, nombre_es, nombre_en, horario_es, horario_en,
                    condiciones_es, condiciones_en, contacto_publico, orden
             FROM servicios WHERE estado = 'publicado' ORDER BY orden, id"
        )->fetchAll();
        return Response::json(['data' => $rows]);
    }

    public function publicShow(Request $request, array $params): Response
    {
        $stmt = Database::pdo()->prepare(
            "SELECT id, slug, nombre_es, nombre_en, horario_es, horario_en,
                    condiciones_es, condiciones_en, contacto_publico
             FROM servicios WHERE slug = ? AND estado = 'publicado' LIMIT 1"
        );
        $stmt->execute([$params['slug']]);
        $row = $stmt->fetch();
        return $row
            ? Response::json(['data' => $row])
            : Response::error('not_found', 'No encontrado', 404);
    }

    public function adminIndex(Request $request, array $params): Response
    {
        $pg = Pagination::fromRequest($request);
        $pdo = Database::pdo();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM servicios')->fetchColumn();
        $stmt = $pdo->prepare('SELECT * FROM servicios ORDER BY orden LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $pg->limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $pg->offset, PDO::PARAM_INT);
        $stmt->execute();
        return Response::json($pg->envelope($stmt->fetchAll(), $total));
    }

    public function adminShow(Request $request, array $params): Response
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM servicios WHERE id = ?');
        $stmt->execute([(int) $params['id']]);
        $row = $stmt->fetch();
        return $row ? Response::json(['data' => $row]) : Response::error('not_found', 'No encontrado', 404);
    }

    public function adminStore(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, [
            'slug', 'nombre_es', 'nombre_en', 'horario_es', 'horario_en',
            'condiciones_es', 'condiciones_en', 'contacto_publico', 'estado', 'orden',
        ]);
        if (empty($data['slug']) || empty($data['nombre_es'])) {
            return Response::error('validation_error', 'slug y nombre_es requeridos', 422);
        }
        $cols = array_keys($data);
        Database::pdo()->prepare(
            'INSERT INTO servicios (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')'
        )->execute(array_values($data));
        return Response::json(['id' => (int) Database::pdo()->lastInsertId()], 201);
    }

    public function adminPatch(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, [
            'slug', 'nombre_es', 'nombre_en', 'horario_es', 'horario_en',
            'condiciones_es', 'condiciones_en', 'contacto_publico', 'estado', 'orden',
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
        Database::pdo()->prepare('UPDATE servicios SET ' . implode(',', $sets) . ' WHERE id = ?')->execute($vals);
        return Response::json(['ok' => true]);
    }

    public function adminDelete(Request $request, array $params): Response
    {
        Database::pdo()->prepare("UPDATE servicios SET estado = 'archivado' WHERE id = ?")
            ->execute([(int) $params['id']]);
        return Response::noContent();
    }
}
