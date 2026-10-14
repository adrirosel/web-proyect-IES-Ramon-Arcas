<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\JsonBody;

final class EnlacesController
{
    public function publicIndex(Request $request, array $params): Response
    {
        $where = "estado = 'publicado'";
        $bind = [];
        if ($g = $request->query('grupo')) {
            $where .= ' AND grupo = ?';
            $bind[] = $g;
        }
        $stmt = Database::pdo()->prepare(
            "SELECT id, grupo, titulo_es, titulo_en, url, orden FROM enlaces WHERE $where ORDER BY orden, id"
        );
        $stmt->execute($bind);
        return Response::json(['data' => $stmt->fetchAll()]);
    }

    public function adminIndex(Request $request, array $params): Response
    {
        return Response::json(['data' => Database::pdo()->query('SELECT * FROM enlaces ORDER BY grupo, orden')->fetchAll()]);
    }

    public function adminStore(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, ['grupo', 'titulo_es', 'titulo_en', 'url', 'orden', 'estado']);
        if (empty($data['grupo']) || empty($data['titulo_es']) || empty($data['url'])) {
            return Response::error('validation_error', 'grupo, titulo_es y url requeridos', 422);
        }
        $cols = array_keys($data);
        Database::pdo()->prepare(
            'INSERT INTO enlaces (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')'
        )->execute(array_values($data));
        return Response::json(['id' => (int) Database::pdo()->lastInsertId()], 201);
    }

    public function adminPatch(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, ['grupo', 'titulo_es', 'titulo_en', 'url', 'orden', 'estado']);
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
        Database::pdo()->prepare('UPDATE enlaces SET ' . implode(',', $sets) . ' WHERE id = ?')->execute($vals);
        return Response::json(['ok' => true]);
    }

    public function adminDelete(Request $request, array $params): Response
    {
        Database::pdo()->prepare('DELETE FROM enlaces WHERE id = ?')->execute([(int) $params['id']]);
        return Response::noContent();
    }
}
