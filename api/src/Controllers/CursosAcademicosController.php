<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\JsonBody;

final class CursosAcademicosController
{
    public function publicIndex(Request $request, array $params): Response
    {
        $rows = Database::pdo()->query(
            'SELECT id, etiqueta, inicio, fin, actual FROM cursos_academicos ORDER BY inicio DESC'
        )->fetchAll();
        return Response::json(['data' => $rows]);
    }

    public function publicShow(Request $request, array $params): Response
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, etiqueta, inicio, fin, actual FROM cursos_academicos WHERE id = ?'
        );
        $stmt->execute([(int) $params['id']]);
        $row = $stmt->fetch();
        return $row
            ? Response::json(['data' => $row])
            : Response::error('not_found', 'No encontrado', 404);
    }

    public function adminIndex(Request $request, array $params): Response
    {
        return Response::json(['data' => Database::pdo()->query('SELECT * FROM cursos_academicos ORDER BY inicio DESC')->fetchAll()]);
    }

    public function adminStore(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, ['etiqueta', 'inicio', 'fin', 'actual']);
        if (empty($data['etiqueta']) || empty($data['inicio']) || empty($data['fin'])) {
            return Response::error('validation_error', 'etiqueta, inicio y fin requeridos', 422);
        }
        $pdo = Database::pdo();
        if (!empty($data['actual'])) {
            $pdo->exec('UPDATE cursos_academicos SET actual = 0');
        }
        $cols = array_keys($data);
        $pdo->prepare(
            'INSERT INTO cursos_academicos (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')'
        )->execute(array_values($data));
        return Response::json(['id' => (int) $pdo->lastInsertId()], 201);
    }

    public function adminPatch(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, ['etiqueta', 'inicio', 'fin', 'actual']);
        $pdo = Database::pdo();
        if (!empty($data['actual'])) {
            $pdo->exec('UPDATE cursos_academicos SET actual = 0');
        }
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
        $pdo->prepare('UPDATE cursos_academicos SET ' . implode(',', $sets) . ' WHERE id = ?')->execute($vals);
        return Response::json(['ok' => true]);
    }
}
