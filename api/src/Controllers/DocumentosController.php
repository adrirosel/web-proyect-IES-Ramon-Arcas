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

final class DocumentosController
{
    public function publicCategorias(Request $request, array $params): Response
    {
        $rows = Database::pdo()->query(
            'SELECT id, slug, nombre_es, nombre_en, orden FROM categorias_documentos ORDER BY orden, id'
        )->fetchAll();
        return Response::json(['data' => $rows]);
    }

    public function publicIndex(Request $request, array $params): Response
    {
        $pg = Pagination::fromRequest($request);
        $where = PublishedScope::sql('d');
        $bind = [];
        if ($cat = $request->query('categoria')) {
            $where .= ' AND c.slug = ?';
            $bind[] = $cat;
        }
        if ($ca = $request->query('curso_academico')) {
            $where .= ' AND d.curso_academico_id = ?';
            $bind[] = (int) $ca;
        }
        if ($ens = $request->query('ensenanza_id')) {
            $where .= ' AND d.ensenanza_id = ?';
            $bind[] = (int) $ens;
        }

        $pdo = Database::pdo();
        $countSql = "SELECT COUNT(*) FROM documentos d
                     INNER JOIN categorias_documentos c ON c.id = d.categoria_id
                     WHERE $where";
        $c = $pdo->prepare($countSql);
        $c->execute($bind);
        $total = (int) $c->fetchColumn();

        $sql = "SELECT d.id, d.titulo_es, d.titulo_en, d.descripcion_es, d.descripcion_en,
                       d.categoria_id, c.slug AS categoria_slug, d.curso_academico_id,
                       d.ensenanza_id, d.archivo_id, d.publicado_en,
                       a.ruta AS archivo_ruta, a.mime AS archivo_mime
                FROM documentos d
                INNER JOIN categorias_documentos c ON c.id = d.categoria_id
                INNER JOIN archivos a ON a.id = d.archivo_id
                WHERE $where
                ORDER BY d.id DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($bind as $v) {
            $stmt->bindValue($i++, $v);
        }
        $stmt->bindValue($i++, $pg->limit, PDO::PARAM_INT);
        $stmt->bindValue($i++, $pg->offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['url_descarga'] = '/storage/uploads/' . basename((string) $r['archivo_ruta']);
        }
        return Response::json($pg->envelope($rows, $total));
    }

    public function publicShow(Request $request, array $params): Response
    {
        $stmt = Database::pdo()->prepare(
            'SELECT d.*, a.ruta AS archivo_ruta, a.mime AS archivo_mime, a.nombre_original
             FROM documentos d
             INNER JOIN archivos a ON a.id = d.archivo_id
             WHERE d.id = ? AND ' . PublishedScope::sql('d')
        );
        $stmt->execute([(int) $params['id']]);
        $row = $stmt->fetch();
        if (!$row) {
            return Response::error('not_found', 'No encontrado', 404);
        }
        $row['url_descarga'] = '/storage/uploads/' . basename((string) $row['archivo_ruta']);
        return Response::json(['data' => $row]);
    }

    public function adminCategoriasIndex(Request $request, array $params): Response
    {
        return Response::json(['data' => Database::pdo()->query('SELECT * FROM categorias_documentos ORDER BY orden')->fetchAll()]);
    }

    public function adminCategoriasStore(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, ['slug', 'nombre_es', 'nombre_en', 'orden']);
        if (empty($data['slug']) || empty($data['nombre_es'])) {
            return Response::error('validation_error', 'slug y nombre_es requeridos', 422);
        }
        $cols = array_keys($data);
        Database::pdo()->prepare(
            'INSERT INTO categorias_documentos (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')'
        )->execute(array_values($data));
        return Response::json(['id' => (int) Database::pdo()->lastInsertId()], 201);
    }

    public function adminCategoriasPatch(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, ['slug', 'nombre_es', 'nombre_en', 'orden']);
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
        Database::pdo()->prepare('UPDATE categorias_documentos SET ' . implode(',', $sets) . ' WHERE id = ?')->execute($vals);
        return Response::json(['ok' => true]);
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
        $c = $pdo->prepare("SELECT COUNT(*) FROM documentos WHERE $where");
        $c->execute($bind);
        $total = (int) $c->fetchColumn();
        $stmt = $pdo->prepare("SELECT * FROM documentos WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?");
        $i = 1;
        foreach ($bind as $v) {
            $stmt->bindValue($i++, $v);
        }
        $stmt->bindValue($i++, $pg->limit, PDO::PARAM_INT);
        $stmt->bindValue($i++, $pg->offset, PDO::PARAM_INT);
        $stmt->execute();
        return Response::json($pg->envelope($stmt->fetchAll(), $total));
    }

    public function adminStore(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, [
            'titulo_es', 'titulo_en', 'descripcion_es', 'descripcion_en',
            'categoria_id', 'curso_academico_id', 'ensenanza_id', 'archivo_id',
            'estado', 'publicado_en',
        ]);
        $user = SessionAuth::user();
        $data['autor_id'] = $user['id'] ?? null;
        $data['actualizado_por'] = $user['id'] ?? null;
        if (empty($data['titulo_es']) || empty($data['categoria_id']) || empty($data['archivo_id'])) {
            return Response::error('validation_error', 'titulo_es, categoria_id y archivo_id requeridos', 422);
        }
        $cols = array_keys($data);
        Database::pdo()->prepare(
            'INSERT INTO documentos (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')'
        )->execute(array_values($data));
        return Response::json(['id' => (int) Database::pdo()->lastInsertId()], 201);
    }

    public function adminPatch(Request $request, array $params): Response
    {
        $data = JsonBody::only($request->body, [
            'titulo_es', 'titulo_en', 'descripcion_es', 'descripcion_en',
            'categoria_id', 'curso_academico_id', 'ensenanza_id', 'archivo_id',
            'estado', 'publicado_en',
        ]);
        $user = SessionAuth::user();
        $data['actualizado_por'] = $user['id'] ?? null;
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
        Database::pdo()->prepare('UPDATE documentos SET ' . implode(',', $sets) . ' WHERE id = ?')->execute($vals);
        return Response::json(['ok' => true]);
    }

    public function adminDelete(Request $request, array $params): Response
    {
        Database::pdo()->prepare("UPDATE documentos SET estado = 'archivado' WHERE id = ?")
            ->execute([(int) $params['id']]);
        return Response::noContent();
    }
}
