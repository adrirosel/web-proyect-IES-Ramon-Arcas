<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\SessionAuth;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Support\JsonBody;
use App\Support\Pagination;
use App\Support\PublishedScope;
use PDO;

final class AvisosController
{
    public function publicIndex(Request $request, array $params): Response
    {
        $pg = Pagination::fromRequest($request);
        $where = PublishedScope::avisoSql();
        $bind = [];
        if ($u = $request->query('ubicacion')) {
            $where .= ' AND ubicacion = ?';
            $bind[] = $u;
        }
        $pdo = Database::pdo();
        $c = $pdo->prepare("SELECT COUNT(*) FROM avisos WHERE $where");
        $c->execute($bind);
        $total = (int) $c->fetchColumn();
        $sql = "SELECT id, titulo_es, titulo_en, texto_es, texto_en, prioridad, ubicacion, destinatarios,
                       visible_desde, visible_hasta, publicado_en
                FROM avisos WHERE $where ORDER BY prioridad DESC, id DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($bind as $v) {
            $stmt->bindValue($i++, $v);
        }
        $stmt->bindValue($i++, $pg->limit, PDO::PARAM_INT);
        $stmt->bindValue($i++, $pg->offset, PDO::PARAM_INT);
        $stmt->execute();
        return Response::json($pg->envelope($stmt->fetchAll(), $total));
    }

    public function publicShow(Request $request, array $params): Response
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM avisos WHERE id = ? AND ' . PublishedScope::avisoSql()
        );
        $stmt->execute([(int) $params['id']]);
        $row = $stmt->fetch();
        if (!$row) {
            return Response::error('not_found', 'No encontrado', 404);
        }
        return Response::json(['data' => $row]);
    }

    public function adminIndex(Request $request, array $params): Response
    {
        $pg = Pagination::fromRequest($request);
        $where = '1=1';
        $bind = [];
        if ($e = $request->query('estado')) {
            $where .= ' AND estado = ?';
            $bind[] = $e;
        }
        $pdo = Database::pdo();
        $c = $pdo->prepare("SELECT COUNT(*) FROM avisos WHERE $where");
        $c->execute($bind);
        $total = (int) $c->fetchColumn();
        $stmt = $pdo->prepare("SELECT * FROM avisos WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?");
        $i = 1;
        foreach ($bind as $v) {
            $stmt->bindValue($i++, $v);
        }
        $stmt->bindValue($i++, $pg->limit, PDO::PARAM_INT);
        $stmt->bindValue($i++, $pg->offset, PDO::PARAM_INT);
        $stmt->execute();
        return Response::json($pg->envelope($stmt->fetchAll(), $total));
    }

    public function adminShow(Request $request, array $params): Response
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM avisos WHERE id = ?');
        $stmt->execute([(int) $params['id']]);
        $row = $stmt->fetch();
        return $row ? Response::json(['data' => $row]) : Response::error('not_found', 'No encontrado', 404);
    }

    public function adminStore(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, [
            'titulo_es', 'titulo_en', 'texto_es', 'texto_en', 'prioridad', 'ubicacion',
            'destinatarios', 'estado', 'visible_desde', 'visible_hasta', 'publicado_en',
        ]);
        $user = SessionAuth::user();
        $data['autor_id'] = $user['id'] ?? null;
        $data['actualizado_por'] = $user['id'] ?? null;
        if (empty($data['titulo_es']) || empty($data['texto_es'])) {
            return Response::error('validation_error', 'titulo_es y texto_es requeridos', 422);
        }
        $cols = array_keys($data);
        $sql = 'INSERT INTO avisos (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
        Database::pdo()->prepare($sql)->execute(array_values($data));
        return Response::json(['id' => (int) Database::pdo()->lastInsertId()], 201);
    }

    public function adminPatch(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $data = JsonBody::only($request->body, [
            'titulo_es', 'titulo_en', 'texto_es', 'texto_en', 'prioridad', 'ubicacion',
            'destinatarios', 'estado', 'visible_desde', 'visible_hasta', 'publicado_en',
        ]);
        $user = SessionAuth::user();
        $data['actualizado_por'] = $user['id'] ?? null;
        if ($data === []) {
            return Response::error('validation_error', 'Sin campos', 422);
        }
        $sets = [];
        $vals = [];
        foreach ($data as $k => $v) {
            $sets[] = "$k = ?";
            $vals[] = $v;
        }
        $vals[] = $id;
        Database::pdo()->prepare('UPDATE avisos SET ' . implode(',', $sets) . ' WHERE id = ?')->execute($vals);
        return Response::json(['ok' => true]);
    }

    public function adminDelete(Request $request, array $params): Response
    {
        Database::pdo()->prepare("UPDATE avisos SET estado = 'archivado' WHERE id = ?")
            ->execute([(int) $params['id']]);
        return Response::noContent();
    }
}
